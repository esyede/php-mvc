<?php

defined('BASE') or exit('No direct script access allowed');

class Faker
{
    private $config;

    public function __construct()
    {
        $this->config = config('faker');
    }

    public function maleName($firstName = true, $lastName = true)
    {
        return $this->makeRandomName('male_names', $firstName, $lastName);
    }

    public function femaleName($firstName = true, $lastName = true)
    {
        return $this->makeRandomName('female_names', $firstName, $lastName);
    }

    public function randomName($firstName = true, $lastName = true)
    {
        return $this->makeRandomName('random_names', $firstName, $lastName);
    }

    public function email()
    {
        shuffle($this->config['email_domain']);
        $email = $this->makeRandomWord(4).'.'.$this->makeRandomWord(8);
        $email .= mt_rand(0, 20).'@'.$this->config['email_domain'][0];

        return $email;
    }

    public function date($year = null)
    {
        $year = ! is_numeric($year) ? date('Y') : $year;
        $month = mt_rand(1, 12);
        $month = ($month < 10) ? '0'.$month : $month;
        $day = mt_rand(1, 31);

        $dayInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $day = ($day > $dayInMonth) ? mt_rand(1, $dayInMonth) : $day;
        $day = ($day < 10) ? '0'.$day : $day;

        $firstPart = $year.'-'.$month.'-'.$day;

        $hour = mt_rand(1, 24);
        $mins = mt_rand(0, 60);
        $secs = mt_rand(0, 60);
        $hour = ($hour < 10) ? '0'.$hour : $hour;
        $mins = ($mins < 10) ? '0'.$mins : $mins;
        $secs = ($secs < 10) ? '0'.$secs : $secs;

        $secondPart = $hour.':'.$mins.':'.$secs;

        return $firstPart.' '.$secondPart;
    }

    public function address()
    {
        $prefix = $this->config['address_prefix'];
        $suffix = $this->config['address_suffix'];

        $key1 = mt_rand(0, (count($prefix) - 1));
        $key2 = mt_rand(0, (count($suffix) - 1));

        $address = mt_rand(100, 800).' '.$prefix[$key1].' '.
        ucfirst($this->makeRandomWord(5)).', '.
        ucfirst($this->makeRandomWord(5)).$suffix[$key2].', '.
        ucfirst($this->makeRandomWord(4)).' '.
        strtoupper($this->makeRandomWord(2)).' '.
        mt_rand(2000, 80000).'-'.mt_rand(3000, 10000);

        return $address;
    }

    public function phone($extension = true)
    {
        $phone = mt_rand(100, 999).
        '-'.mt_rand(109, 809).
        '-0'.mt_rand(100, 999).
        ($extension ? ' x'.mt_rand(1000, 9999) : '');

        return $phone;
    }

    public function text($count = 1, $max = 20, $standard = true)
    {
        $text = '';
        if ($standard) {
            $text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, '.
                'sed do eiusmod tempor incididunt ut labore et dolore magna '.
                'aliqua.';
        }

        $random = explode(
            ' ',
            'a ab ad accusamus adipisci alias aliquam amet animi aperiam '.
            'architecto asperiores aspernatur assumenda at atque aut beatae '.
            'blanditiis cillum commodi consequatur corporis corrupti culpa '.
            'cum cupiditate debitis delectus deleniti deserunt dicta '.
            'dignissimos distinctio dolor ducimus duis ea eaque earum eius '.
            'eligendi enim eos error esse est eum eveniet ex excepteur '.
            'exercitationem expedita explicabo facere facilis fugiat harum '.
            'hic id illum impedit in incidunt ipsa iste itaque iure iusto '.
            'laborum laudantium libero magnam maiores maxime minim minus '.
            'modi molestiae mollitia nam natus necessitatibus nemo neque '.
            'nesciunt nihil nisi nobis non nostrum nulla numquam occaecati '.
            'odio officia omnis optio pariatur perferendis perspiciatis '.
            'placeat porro possimus praesentium proident quae quia quibus '.
            'quo ratione recusandae reiciendis rem repellat reprehenderit '.
            'repudiandae rerum saepe sapiente sequi similique sint soluta '.
            'suscipit tempora tenetur totam ut ullam unde vel veniam vero '.
            'vitae voluptas'
        );

        $max = ($max <= 3) ? 4 : $max;
        for ($i = 0, $add = $count - (int) $standard; $i < $add; ++$i) {
            shuffle($random);
            $words = array_slice($random, 0, mt_rand(3, $max));
            $text .= (! $standard && 0 == $i ? '' : ' ').ucfirst(implode(' ', $words)).'.';
        }

        return $text;
    }

    public function makeRandomWord($length = 6)
    {
        $word = '';
        $vowels = ['a', 'e', 'i', 'o', 'u'];
        $consonants = [
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
            'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z',
        ];

        $max = $length / 2;

        for ($i = 1; $i <= $max; ++$i) {
            $word .= $consonants[rand(0, 19)];
            $word .= $vowels[rand(0, 4)];
        }

        return $word;
    }

    private function makeRandomName($config, $firstName = true, $lastName = true)
    {
        $names = [];
        if ('random_names' == $config) {
            $names = $this->config['male_names'] + $this->config['female_names'];
            shuffle($names);
            shuffle($names);
        } else {
            $names = $this->config[$config];
        }

        $count = count($names);
        $random = $this->makeRandomWord(mt_rand(6, 8));
        // replace least used chars
        $const = ['r', 's', 't'];
        shuffle($const);
        $random = str_replace(['x', 'z'], $const[0], $random);

        if (true == $firstName && false == $lastName) {
            return $names[mt_rand(0, $count - 1)];
        } elseif (false == $firstName && true == $lastName) {
            return ucfirst($random);
        } elseif (true == $firstName && true == $lastName) {
            $firstName = $names[mt_rand(0, $count - 1)];
            $lastName = ucfirst($random);

            return $firstName.' '.$lastName;
        }

        return null;
    }
}
