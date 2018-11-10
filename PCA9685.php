<?php
# Copyright (c) 2018 Vasyl Pominchuk
# Author: Vasyl Pominchuk
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.

/**
 * Class PCA9685
 * PHP class to use the PCA9685 PWM servo/LED controller with a Raspberry Pi
 */
class PCA9685
{
    public $i2c_set = '/usr/sbin/i2cset -y';
    public $i2c_get = '/usr/sbin/i2cget -y';
    public $i2c_address;
    public $i2c_bus;

    const RESTART = 0b10000000;
    const SLEEP = 0b10000;

    const MODE1 = 0;
    const PRE_SCALE = 254;

    const LED0_ON_L = 6;

    public function __construct($i2c_bus = 1, $i2c_address = 0x40)
    {
        $this->i2c_bus = $i2c_bus;
        $this->i2c_address = $i2c_address;

        $this->reset();
        $this->setFrequency(1000);
    }

    /**
     * Restart controller
     */
    public function reset() {
        $this->write(self::MODE1, self::RESTART);
        usleep(10);
    }

    /**
     * Enter in to the sleep mode.
     * Set the SLEEP bit in MODE1. This turns off the internal oscillator
     */
    public function sleep() {
        $oldMode = $this->read(self::MODE1);
        $newMode = ($oldMode & 0x7F) | self::SLEEP; // sleep

        $this->write(self::MODE1, $newMode);
    }

    /**
     * @param $register
     * @param $value
     */
    public function write($register, $value) {
        $command = sprintf("%s %d %d %d %d", $this->i2c_set, $this->i2c_bus, $this->i2c_address,
            $register, (int)$value);

        exec($command, $out);
    }

    /**
     * @param $register
     * @param $onLo
     * @param $onHi
     * @param $offLo
     * @param $offHi
     */
    public function writeBlock($register, $onLo, $onHi, $offLo, $offHi) {
        $command = sprintf("%s %d %d %d %d %d %d %d i", $this->i2c_set, $this->i2c_bus, $this->i2c_address,
            $register, $onLo, $onHi, $offLo, $offHi);

        exec($command, $out);
    }

    /**
     * @param $register
     * @return mixed
     */
    public function read($register) {
        $command = sprintf("%s %s %s %d", $this->i2c_get, $this->i2c_bus, $this->i2c_address, $register);

        exec($command, $out);

        return (int)hexdec($out[0]);
    }

    public function setPWMPair($channel, $countOn, $countOff, $count2On, $count2Off, $delay)
    {
        $register = self::LED0_ON_L + 4 * $channel;

        $command = sprintf("%s %d %d %d %d %d %d %d i; sleep %f; %s %d %d %d %d %d %d %d i",
            $this->i2c_set, $this->i2c_bus, $this->i2c_address, $register, $countOn & 0xFF, $countOn >> 8,
            $countOff & 0xFF, $countOff >> 8, $delay, $this->i2c_set, $this->i2c_bus, $this->i2c_address, $register,
            $count2On & 0xFF, $count2On >> 8, $count2Off & 0xFF, $count2Off >> 8);

        exec($command, $out);
    }

    public function setPWM($channel, $countOn, $countOff)
    {
        $register = self::LED0_ON_L + 4 * $channel;

        $this->writeBlock($register, $countOn & 0xFF, $countOn >> 8, $countOff & 0xFF, $countOff >> 8);
    }

    public function setAll($countOff, $countOn = 0)
    {
        $this->write(250, $countOn & 0xFF);
        $this->write(251, $countOn >> 8);
        $this->write(252, $countOff & 0xFF);
        $this->write(253, $countOff >> 8);
    }

    /**
     * Set the PWM frequency to the provided value in hertz.
     * @param $frequency
     */
    public function setFrequency($frequency)
    {
        $value = round(25000000 / (4096 * $frequency)) - 1;

        // the PCA9685 has frequency limits, we'll be sure we're within those
        if($value < 3) $value = 3;
        if($value > 255) $value = 255;

        $oldMode = $this->read(self::MODE1);
        $newMode = ($oldMode & 0x7F) | self::SLEEP; // sleep

        $this->write(self::MODE1, $newMode);

        $this->write(self::PRE_SCALE, $value);

        $this->write(self::MODE1, $oldMode);

        usleep(5);
        $this->write(self::MODE1, $oldMode | 0xa0);  //  This sets the MODE1 register to turn on auto increment.
    }
}
