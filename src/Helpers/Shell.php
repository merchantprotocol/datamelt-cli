<?php
/**
 * NOTICE OF LICENSE
 *
 * MIT License
 * 
 * Copyright (c) 2019 Merchant Protocol
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * 
 * @category   merchantprotocol
 * @package    merchantprotocol/protocol
 * @copyright  Copyright (c) 2019 Merchant Protocol, LLC (https://merchantprotocol.com/)
 * @license    MIT License
 */
namespace Datamelt\Helpers;

Class Shell
{
    CONST outputfile = 'background_process.log';

    CONST MAC = 'mac';
    CONST LINUX = 'linux';
    CONST CYGWIN = 'cygwin';
    CONST MINGW = 'mingw';

    /**
     * Return the process
     *
     * @param [type] $process
     * @param boolean $column
     * @return boolean
     */
    public static function hasProcess( $process, $column = false )
    {
        $processes = Self::getProcesses();
        $has = [];
        foreach ($processes as $ps) {
            $string = implode(" ", $ps);
            if (strpos($string, $process)!==false) {
                $has[] = $ps;
            }
        }
        return $has;
    }

    /**
     * Returns an array of processes
     *
     * @param boolean $process
     * @return boolean
     */
    public static function getProcesses()
    {
        $dash = '';
        if (SELF::MAC != self::getOS()) {
            $dash = '-';
        }
        $cmd = <<<CMD
        ps -exo %mem,%cpu,pid,command | awk '{OORS=ORS; ORS=""; print $1"   "$2"   "$3"   "; ORS=OORS; $1="";$2="";$3=""; print $0; }'
        CMD;

        $processes = Shell::run($cmd);
        return self::shellTableToArray($processes);
    }

    /**
     * Function that converts shell tables to an array
     *
     * @param [type] $blob
     * @return void
     */
    public static function shellTableToArray( $blob )
    {
        $processes = explode(PHP_EOL, $blob);

        $keys = array_shift($processes);
        $keys = preg_split("/\s{2,}/", $keys);

        $psArray = [];
        foreach ($processes as $ps) {
            $values = preg_split("/\s{2,}/", $ps);
            $row = [];
            foreach ($values as $key => $value) {
                $row[ $keys[$key] ] = $value;
            }
            $psArray[] = $row;
        }
        return $psArray;
    }

    /**
     * returns the process
     *
     * @param boolean $process
     * @return boolean
     */
    public static function getProcess( $process = false )
    {
        $filter = "";
        if ($process) {
            $filter = "| grep $process";
        }

        $cmd = "ps aux $filter";
        $processes = Shell::run($cmd);
        $processes = explode(PHP_EOL, $processes);

        foreach ($processes as $key => $_ps) {
            if (strpos($_ps, "0:00.00 grep $process")!==false) {
                unset($processes[$key]);
            }
            if (strpos($_ps, $cmd)!==false) {
                unset($processes[$key]);
            }
        }
        var_dump($processes);

    }

    /**
     * determines what operating system we're running
     *
     * @return void
     */
    public static function getOS()
    {
        $uname = Shell::run("uname -a");

        if (strpos($uname, "Linux")!==false) {
            return SELF::LINUX;
        } elseif (strpos($uname, "Darwin")!==false) {
            return SELF::MAC;
        } elseif (strpos($uname, "CYGWIN")!==false) {
            return SELF::CYGWIN;
        } elseif (strpos($uname, "MINGW")!==false) {
            return SELF::MINGW;
        }

        return 'unknown';
    }

    /**
     * Provides a passthrough exec option
     *
     * @param [type] $command
     * @return void
     */
    public static function passthru( $command )
    {
        $descriptorSpec = array(
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        );
        $pipes = array();
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (is_resource($process)) {
            proc_close($process);
        }
        return 0;
    }

    /**
     * Runs the shell command and returns the result
     *
     * @param [string] $command
     * @param [int] $return_var
     * @return void
     */
    public static function run( $command, &$return_var = null )
    {
        $response = null;
        exec("$command 2>&1", $response, $return_var);

        if (is_array($response)) {
            $response = implode(PHP_EOL, $response);
        }

        return $response;
    }

    /**
     * Run this command in the background. This launches the command $cmd, redirects 
     * the command output to $outputfile, and writes the process id to $pidfile.
     *
     * @param [type] $command
     * @return void
     */
    public static function background( $command )
    {
        if (!file_exists(self::outputfile)) {
            $dir = dirname(self::outputfile);
            if (!is_dir($dir)) {
                self::run("mkdir -p $dir");
            }
            if (is_dir($dir)) {
                touch(self::outputfile);
            }
        }

        $command = sprintf("%s > %s 2>&1 & echo $!", $command, self::outputfile);
        exec($command, $response);

        if (is_array($response)) {
            $response = array_pop($response);
        }
        return $response;
    }

    /**
     * Allows you to check on a background running process
     *
     * @param [type] $pid
     * @return boolean
     */
    public static function isRunning( $pid )
    {
        try{
            $result = shell_exec(sprintf("ps %d", $pid));
            if( count(preg_split("/\n/", $result)) > 2){
                return true;
            }
        }catch(Exception $e){}

        return false;
    }
}