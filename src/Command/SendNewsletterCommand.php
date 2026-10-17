<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\VideoGameRepository;
use App\Service\MailerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:send-newsletter',
    description: 'Envoie la newsletter des jeux qui sortent dans les 7 prochains jours.',
)]
#[AsCronTask('30 8 * * 1')]
class SendNewsletterCommand extends Command
{
    private MailerService $mailerService;
    private UserRepository $userRepository;
    private VideoGameRepository $videoGameRepository;

    public function __construct(MailerService $mailerService, UserRepository $userRepository,
                                VideoGameRepository $videoGameRepository)
    {
        parent::__construct();
        $this->mailerService = $mailerService;
        $this->userRepository = $userRepository;
        $this->videoGameRepository = $videoGameRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $today = new \DateTime('today');
        $nextWeek = new \DateTime('today');
        $nextWeek->modify('+7 days');

        $nextVideoGames = [];
        foreach ($this->videoGameRepository->findAll() as $videoGame) {
            if ($videoGame->getDeletedAt() === null
                && $videoGame->getReleaseDate() >= $today
                && $videoGame->getReleaseDate() <= $nextWeek) {
                $nextVideoGames[] = $videoGame;
            }
        }

        $nbEmails = 0;
        foreach ($this->userRepository->findAll() as $user) {
            if ($user->isSubcriptionToNewsletter() && $user->getDeletedAt() === null) {
                $this->mailerService->sendNewsletter($user->getEmail(), $nextVideoGames);
                $nbEmails++;
            }
        }

        $io->success('Newsletter envoyée à ' . $nbEmails . ' abonné(s), ' . count($nextVideoGames) . ' jeu(x) à venir.');

        return Command::SUCCESS;
    }
}
