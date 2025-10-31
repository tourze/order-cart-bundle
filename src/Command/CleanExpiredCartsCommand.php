<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Repository\CartItemRepository;

#[AsCommand(
    name: self::NAME,
    description: 'Clean expired cart items',
)]
final class CleanExpiredCartsCommand extends Command
{
    public const NAME = 'order-cart:clean-expired';

    public function __construct(
        private readonly CartItemRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Days to keep cart items', '30')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run in dry-run mode')
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Batch size for deletion', '100')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $daysOption = $input->getOption('days');
        $days = is_numeric($daysOption) ? (int) $daysOption : 30;
        $dryRun = (bool) $input->getOption('dry-run');
        $batchSizeOption = $input->getOption('batch-size');
        $batchSize = is_numeric($batchSizeOption) ? (int) $batchSizeOption : 100;

        if ($dryRun) {
            $io->note('Running in dry-run mode. No items will be deleted.');
        }

        $io->title('Cleaning Expired Cart Items');

        $expirationDate = new \DateTimeImmutable("-{$days} days");
        $io->info(sprintf('Removing cart items older than %s', $expirationDate->format('Y-m-d H:i:s')));

        // Count expired items
        $totalExpired = $this->countExpiredItems($expirationDate);

        if (0 === $totalExpired) {
            $io->success('No expired cart items found.');

            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d expired cart items', $totalExpired));

        if ($dryRun) {
            $io->success('Dry-run completed. Would have deleted ' . $totalExpired . ' items.');

            return Command::SUCCESS;
        }

        // Delete in batches
        $progressBar = $io->createProgressBar($totalExpired);
        $progressBar->start();

        $deletedCount = 0;
        while ($deletedCount < $totalExpired) {
            $deleted = $this->deleteExpiredBatch($expirationDate, $batchSize);
            $deletedCount += $deleted;
            $progressBar->advance($deleted);

            if (0 === $deleted) {
                break;
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf('Successfully deleted %d expired cart items.', $deletedCount));

        return Command::SUCCESS;
    }

    private function countExpiredItems(\DateTimeInterface $expirationDate): int
    {
        $result = $this->repository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.updateTime < :expirationDate')
            ->setParameter('expirationDate', $expirationDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    private function deleteExpiredBatch(\DateTimeInterface $expirationDate, int $batchSize): int
    {
        /** @var list<CartItem> $items */
        $items = $this->repository->createQueryBuilder('c')
            ->where('c.updateTime < :expirationDate')
            ->setParameter('expirationDate', $expirationDate)
            ->setMaxResults($batchSize)
            ->getQuery()
            ->getResult()
        ;

        $count = count($items);

        foreach ($items as $item) {
            $this->repository->remove($item);
        }

        return $count;
    }
}
