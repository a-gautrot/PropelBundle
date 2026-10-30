<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use RuntimeException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * # FormattingHelpers
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
trait FormattingHelpers
{
    /**
     * Asks a confirmation to the user.
     *
     * The question will be asked until the user answers by nothing, yes, or no.
     *
     * @param InputInterface $input An Input instance
     * @param OutputInterface $output An Output instance
     * @param string $question The question to ask
     * @param bool $default The default answer if the user enters nothing
     *
     * @return bool true if the user has confirmed, false otherwise
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws ConsoleRuntimeException
     */
    protected function askConfirmation(InputInterface $input, OutputInterface $output, string $question, bool $default = true): bool
    {
        $question = new Question($question);
        do {
            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getRequiredHelperSet()->get('question');
            $answer = $questionHelper->ask($input, $output, $question);
        } while ($answer && !in_array(strtolower($answer[0]), ['y', 'n']));

        if ($default === false) {
            return $answer && strtolower($answer[0]) == 'y';
        }

        return !$answer || strtolower($answer[0]) == 'y';
    }

    /**
     * @param OutputInterface $output The output.
     * @param string $filename The filename.
     * @return void
     */
    protected function writeNewFile(OutputInterface $output, string $filename): void
    {
        $output->writeln(">>  <info>File+</info>    $filename");
    }

    /**
     * @param OutputInterface $output The output.
     * @param string $directory The directory.
     * @return void
     */
    protected function writeNewDirectory(OutputInterface $output, string $directory): void
    {
        $output->writeln(">>  <info>Dir+</info>     $directory");
    }

    /**
     * Renders an error message if a task has failed.
     *
     * @param OutputInterface $output The output.
     * @param string $taskName A task name.
     * @param bool $more Whether to add a 'more details' message or not.
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function writeTaskError(OutputInterface $output, string $taskName, bool $more = true): void
    {
        $moreText = $more ? ' To get more details, run the command with the "--verbose" option.' : '';

        $this->writeSection($output, [
            '[Propel] Error',
            '',
            "An error has occurred during the \"$taskName\" task process.$moreText"
        ], 'fg=white;bg=red');
    }

    /**
     * Comes from the SensioGeneratorBundle.
     * @see https://github.com/sensiolabs/SensioGeneratorBundle/blob/2.4/Command/Helper/DialogHelper.php#L52
     *
     * @param OutputInterface $output The output.
     * @param string|string[] $text A text message.
     * @param string $style A style to apply on the section.
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function writeSection(OutputInterface $output, $text, string $style = 'bg=blue;fg=white'): void
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getRequiredHelperSet()->get('formatter');

        $output->writeln([
            '',
            $formatter->formatBlock($text, $style, true),
            '',
        ]);
    }
}
