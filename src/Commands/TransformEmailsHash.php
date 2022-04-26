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
namespace Datamelt\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Datamelt\Helpers\Dir;
use Datamelt\Helpers\Shell;

Class TransformEmailsHash extends Command 
{
    protected static $defaultName = 'transform:emails:hash';
    protected static $defaultDescription = 'Looks for emails in the first column of a csv file';

    protected function configure(): void
    {
        // ...
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp(<<<HELP
            Looks for emails in the first column of a csv file. Recreates a new csv with the email, domain, md5, md5 uppercase, sha1, sha1 uppercase, sha256, sha256 uppercase.

            HELP)
        ;
        $this
            // configure an argument
            ->addArgument('source', InputArgument::REQUIRED, 'Source path')
            ->addArgument('destination', InputArgument::OPTIONAL, 'Destination path')
            ->addArgument('rowsperFile', InputArgument::OPTIONAL, 'Rows per new file', '50000000')
            ->addOption('daemon', 'd', InputOption::VALUE_OPTIONAL, 'Run as a background service', false)
            // ...
        ;
    }

    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daemon = $input->getOption('daemon', false);
        if (is_null($daemon)) $daemon = true;

        $source = DIR::realpath($input->getArgument('source'));
        $dest = $input->getArgument('destination', false);
        if (!$dest) {
            $dest = rtrim($source, "/")."-completed";
        }

        // not currently in use
        $rowsperFile = $input->getArgument('rowsperFile', 1);

        $command = BIN_DIR."Transform/emails-hash '$source' '$dest' $rowsperFile";
        if ($daemon) {
            Shell::background($command." 1");
        } else {
            Shell::passthru($command);
        }

        return Command::SUCCESS;
    }

}