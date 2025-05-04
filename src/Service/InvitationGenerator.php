<?php

namespace App\Service;

use App\Entity\EventRegistration;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvitationGenerator
{
    private Environment $twig;
    private ParameterBagInterface $params;

    public function __construct(Environment $twig, ParameterBagInterface $params)
    {
        $this->twig = $twig;
        $this->params = $params;
    }

    public function generateInvitation(EventRegistration $registration): string
    {
        // Increase memory limit temporarily for this operation
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '256M');

        try {
            // Setup DOMPDF options with memory optimizations
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('chroot', $this->params->get('kernel.project_dir') . '/public');
            $options->set('enable_font_subsetting', true);

            // Initialize DOMPDF
            $dompdf = new Dompdf($options);

            // Calculate image paths - use relative paths instead of base64 encoding
            $projectDir = $this->params->get('kernel.project_dir');
            $backgroundImagePath = '/assets/img/invitation.png';
            $logoPath = '/assets/img/logo.jpg';

            // Generate HTML content for the invitation
            $html = $this->twig->render('emails/invitation_template.html.twig', [
                'registration' => $registration,
                'guests' => $registration->getGuests()->toArray(),
                'date' => new \DateTime('2025-05-30'),
                'background_image_path' => $backgroundImagePath,
                'logo_path' => $logoPath,
            ]);

            // Load HTML into DOMPDF
            $dompdf->loadHtml($html);

            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');

            // Render PDF with lower resolution
            $dompdf->render();

            // Get PDF as string
            $output = $dompdf->output();

            // Restore original memory limit
            ini_set('memory_limit', $originalMemoryLimit);

            return $output;
        } catch (\Exception $e) {
            // Restore original memory limit even if an error occurs
            ini_set('memory_limit', $originalMemoryLimit);

            // Log the error
            error_log('PDF Generation Error: ' . $e->getMessage());

            // Re-throw or handle as needed
            throw $e;
        }
    }
}