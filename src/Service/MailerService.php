<?php

namespace App\Service;

use App\Entity\Reservation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(
        private readonly ?MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendReservationConfirmation(Reservation $reservation): void
    {
        $parent = $reservation->getChild()?->getParent();
        if (null === $this->mailer || null === $parent?->getEmail()) {
            return;
        }

        $email = (new Email())
            ->to($parent->getEmail())
            ->subject('Confirmation de reservation')
            ->text(sprintf(
                'La reservation de %s pour l\'activite "%s" a bien ete enregistree.',
                $reservation->getChild()?->getFullName(),
                $reservation->getActivity()?->getTitre()
            ));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface|\Throwable $exception) {
            $this->logger->warning('Email de reservation non envoye.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
