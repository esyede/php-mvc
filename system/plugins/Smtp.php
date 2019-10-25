<?php

class Smtp
{
    const CRLF = "\r\n";
    const TLS = 'tcp';
    const SSL = 'ssl';
    const OK = 250;

    protected $config;
    protected $socket;

    protected $subject;
    protected $recipients = [];
    protected $cc = [];
    protected $bcc = [];
    protected $sender = [];
    protected $reply_to = [];
    protected $attachments = [];
    protected $protocol = null;
    protected $port = null;
    protected $text_message = null;
    protected $html_message = null;

    protected $is_html = false;
    protected $is_tls = false;
    protected $logs = [];
    protected $charset = 'UTF-8';
    protected $headers = [];

    public function __construct()
    {
        $this->config = get_instance()->config('smtp');
        $this->charset($this->config['charset']);
        $this->protocol($this->config['protocol']);
        $this->port($this->config['port']);
        $this->headers['X-Mailer'] = $this->config['x-mailer'];
        $this->headers['MIME-Version'] = '1.0';
    }

    public function from($address, $name = null)
    {
        $this->sender = [$address, $name];

        return $this;
    }

    public function to($address, $name = null)
    {
        $this->recipients[] = [$address, $name];

        return $this;
    }

    public function cc($address, $name = null)
    {
        $this->cc[] = [$address, $name];

        return $this;
    }

    public function bcc($address, $name = null)
    {
        $this->bcc[] = [$address, $name];

        return $this;
    }

    public function replyto($address, $name = null)
    {
        $this->reply_to[] = [$address, $name];

        return $this;
    }

    public function attach($attachment)
    {
        if (! is_file($attachment)) {
            throw new \Exception('Attachment not found: '.$attachment);
        }

        $this->attachments[] = $attachment;

        return $this;
    }

    public function charset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    public function protocol($protocol = null)
    {
        if (self::TLS === $protocol) {
            $this->is_tls = true;
        }

        $this->protocol = $protocol;

        return $this;
    }

    public function port($port = 587)
    {
        $this->port = $port;

        return $this;
    }

    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    public function text($msg)
    {
        $this->text_message = $msg;
        $this->is_html = false;

        return $this;
    }

    public function html($msg)
    {
        $this->html_message = $msg;
        $this->is_html = true;

        return $this;
    }

    public function logs()
    {
        return $this->logs;
    }

    public function send()
    {
        $this->socket = fsockopen(
            $this->getServer(),
            $this->port,
            $errorNumber,
            $errorMessage,
            $this->config['connection_timeout']
        );

        if (blank($this->socket)) {
            return false;
        }

        $this->logs['CONNECTION'] = $this->getResponse();
        $this->logs['HELLO'][1] = $this->sendCommand('EHLO '.$this->config['hostname']);

        if ($this->is_tls) {
            $this->logs['STARTTLS'] = $this->sendCommand('STARTTLS');
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->logs['HELLO'][2] = $this->sendCommand('EHLO '.$this->config['hostname']);
        }

        $this->logs['AUTH'] = $this->sendCommand('AUTH LOGIN');
        $this->logs['USERNAME'] = $this->sendCommand(base64_encode($this->config['username']));
        $this->logs['PASSWORD'] = $this->sendCommand(base64_encode($this->config['password']));
        $this->logs['MAIL_FROM'] = $this->sendCommand('MAIL FROM: <'.$this->sender[0].'>');

        $recipients = array_merge($this->recipients, $this->cc, $this->bcc);
        foreach ($recipients as $address) {
            $this->logs['RECIPIENTS'][] = $this->sendCommand('RCPT TO: <'.$address[0].'>');
        }

        $this->headers['Date'] = date('r');
        $this->headers['Subject'] = $this->subject;
        $this->headers['From'] = $this->formatAddress($this->sender);
        $this->headers['Return-Path'] = $this->formatAddress($this->sender);
        $this->headers['To'] = $this->formatAddressList($this->recipients);

        if (filled($this->reply_to)) {
            $this->headers['Reply-To'] = $this->formatAddressList($this->reply_to);
        }

        if (filled($this->cc)) {
            $this->headers['Cc'] = $this->formatAddressList($this->cc);
        }

        if (filled($this->bcc)) {
            $this->headers['Bcc'] = $this->formatAddressList($this->bcc);
        }

        $boundary = md5(uniqid(random_int(9, 999), true));

        if (filled($this->attachments)) {
            $this->headers['Content-Type'] = 'multipart/mixed; '.
                'boundary="mixed-'.$boundary.'"';
            $msg = '--mixed-'.$boundary.self::CRLF;
            $msg .= 'Content-Type: multipart/alternative; '.
                'boundary="alt-'.$boundary.'"'.self::CRLF.self::CRLF;
        } else {
            $this->headers['Content-Type'] = 'multipart/alternative; '.
                'boundary="alt-'.$boundary.'"';
        }

        if (filled($this->text_message)) {
            $msg .= '--alt-'.$boundary.self::CRLF;
            $msg .= 'Content-Type: text/plain; charset='.$this->charset.self::CRLF;
            $msg .= 'Content-Transfer-Encoding: base64'.self::CRLF.self::CRLF;
            $msg .= chunk_split(base64_encode($this->text_message)).self::CRLF;
        }

        if (filled($this->html_message)) {
            $msg .= '--alt-'.$boundary.self::CRLF;
            $msg .= 'Content-Type: text/html; charset='.$this->charset.self::CRLF;
            $msg .= 'Content-Transfer-Encoding: base64'.self::CRLF.self::CRLF;
            $msg .= chunk_split(base64_encode($this->html_message)).self::CRLF;
        }

        $msg .= '--alt-'.$boundary.'--'.self::CRLF.self::CRLF;

        if (filled($this->attachments)) {
            foreach ($this->attachments as $attachment) {
                $filename = pathinfo($attachment, PATHINFO_BASENAME);
                $contents = file_get_contents($attachment);
                get_instance()->helper('url');
                $type = get_mime($attachment);

                $msg .= '--mixed-'.$boundary.self::CRLF;
                $msg .= 'Content-Type: '.$type.'; name="'.$filename.'"'.self::CRLF;
                $msg .= 'Content-Disposition: attachment; filename="'.$filename.'"'.self::CRLF;
                $msg .= 'Content-Transfer-Encoding: base64'.self::CRLF.self::CRLF;
                $msg .= chunk_split(base64_encode($contents)).self::CRLF;
            }

            $msg .= '--mixed-'.$boundary.'--';
        }

        $headers = '';
        foreach ($this->headers as $k => $v) {
            $headers .= $k.': '.$v.self::CRLF;
        }

        $data = $headers.self::CRLF.$msg.self::CRLF.'.';

        $this->logs['MESSAGE'] = $msg;
        $this->logs['HEADERS'] = $headers;
        $this->logs['DATA'][1] = $this->sendCommand('DATA');
        $this->logs['DATA'][2] = $this->sendCommand($data);
        $this->logs['QUIT'] = $this->sendCommand('QUIT');
        fclose($this->socket);

        return self::OK == substr($this->logs['DATA'][2], 0, 3);
    }

    protected function getServer()
    {
        return filled($this->protocol)
            ? $this->protocol.'://'.$this->config['server']
            : $this->config['server'];
    }

    protected function getResponse()
    {
        $response = '';
        stream_set_timeout($this->socket, $this->config['response_timeout']);
        while (false !== ($line = fgets($this->socket, 515))) {
            $response .= trim($line)."\n";
            if (' ' == substr($line, 3, 1)) {
                break;
            }
        }

        return trim($response);
    }

    protected function sendCommand($command)
    {
        fputs($this->socket, $command.self::CRLF);

        return $this->getResponse();
    }

    protected function formatAddress($address)
    {
        return (blank($address[1]))
            ? $address[0]
            : '"'.$address[1].'" <'.$address[0].'>';
    }

    protected function formatAddressList(array $addresses)
    {
        $data = [];
        foreach ($addresses as $address) {
            $data[] = $this->formatAddress($address);
        }

        return implode(', ', $data);
    }
}
