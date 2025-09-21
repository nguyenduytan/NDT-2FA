<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Symfony\Command;
use ndtan\TwoFA\Totp\Totp; use ndtan\TwoFA\QR\EndroidQrProvider;
use Symfony\Component\Console\Command\Command; use Symfony\Component\Console\Input\InputArgument; use Symfony\Component\Console\Input\InputInterface; use Symfony\Component\Console\Output\OutputInterface;
final class GenerateSecretCommand extends Command{
    protected static $defaultName='ndt2fa:secret';
    protected function configure():void{ $this->setDescription('Generate TOTP secret and QR data URI')->addArgument('account',InputArgument::REQUIRED)->addArgument('issuer',InputArgument::REQUIRED); }
    protected function execute(InputInterface $input, OutputInterface $output): int{
        $account=(string)$input->getArgument('account'); $issuer=(string)$input->getArgument('issuer');
        $secret=Totp::generateSecret(); $uri=Totp::buildOtpAuthUri($account,$issuer,$secret); $qr=(new EndroidQrProvider())->render($uri,256,true);
        $output->writeln('Secret: '.$secret); $output->writeln('otpauth: '.$uri); $output->writeln('QR (data URI): '.$qr); return Command::SUCCESS;
    }
}
