<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 14.12.17
 * Time: 23:08
 */

namespace App\Benchmark\Random;

class SystemRand extends AbstractRand {

    /**
     * Initializes random generator
     * Must be implemented by derived class
     */
    protected function init()
    {

    }

    /**
     * Returns random unsigned integer. Int size depends on generator's algorithm.
     * Must be implemented by derived class
     *
     * @return int Random number
     */
    public function randomInt()
    {
        return mt_rand();
    }

    public function getMaxInt()
    {
        return mt_getrandmax();
    }


}