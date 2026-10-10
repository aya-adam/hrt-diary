<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-app-secret',
    description: 'Generate a new APP_SECRET key',
)]
class CreateAppSecretCommand extends Command
{
    private const int DEFAULT_NUM_BYTES = 16;
    private ?int $numBytes;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('--bytes', null, InputOption::VALUE_OPTIONAL, 'Number of bytes to be used to generate the secret (default: 16)');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->getOption('bytes')) {
            $this->numBytes = self::DEFAULT_NUM_BYTES;
            return;
        }

        try {
            $validatedBytes = $this->validateBytesInput($input->getOption('bytes'));
        } catch (\InvalidArgumentException $e) {
            $io = new SymfonyStyle($input, $output);
            $io->warning(sprintf("%s Falling back to default (%d).", $e->getMessage(), self::DEFAULT_NUM_BYTES));
        }

        $this->numBytes = !empty($validatedBytes) ? $validatedBytes : self::DEFAULT_NUM_BYTES;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->note(sprintf('Generating new secret (%d bytes)', $this->numBytes));
        $secret = bin2hex(random_bytes($this->numBytes));

        $io->success(sprintf('New APP_SECRET was generated: %s', $secret));

        return Command::SUCCESS;
    }

    protected function validateBytesInput(mixed $value): int
    {
        if (!$value = filter_var($value, FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('The value provided to the option --bytes is not a valid integer value.');
        }

        if ($value <= 0) {
            throw new \InvalidArgumentException('The value provided to the option --bytes must be greater than 0.');
        }

        return $value;
    }
}
