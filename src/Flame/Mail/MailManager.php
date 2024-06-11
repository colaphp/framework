<?php

declare(strict_types=1);

namespace Flame\Mail;

use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * 邮件发送类
 */
class MailManager
{
    /**
     * 邮件配置
     */
    protected array $config;

    /**
     * 邮寄者
     */
    protected Mailer $mailer;

    public function __construct()
    {
        $this->config = config('mail');

        $mailerConfig = $this->config['mailers'][$this->config['default']];
        $transport = Transport::fromDsn(sprintf('%s://%s:%s@%s:%d',
            $mailerConfig['transport'],
            $mailerConfig['username'],
            $mailerConfig['password'],
            $mailerConfig['host'],
            $mailerConfig['port'],
        ));

        $this->mailer = new Mailer($transport);
    }

    /**
     * 发送邮件
     *
     * @throws TransportExceptionInterface
     */
    public function send(string $to, string $title, string $content = '', string $template = '', array $data = []): void
    {
        if (empty($template)) {
            $this->sendByContent($to, $title, $content);
        } else {
            $this->sendByTemplate($to, $title, $template, $data);
        }
    }

    /**
     * 发送内容邮件
     *
     * @throws TransportExceptionInterface
     */
    public function sendByContent($to, $title, $content): void
    {
        $message = (new Email())
            ->from(new Address($this->config['from']['address']))
            ->to(new Address($to))
            ->subject($title)
            ->html($content);

        $this->mailer->send($message);
    }

    /**
     * 发送模板内容邮件
     *
     * @throws TransportExceptionInterface
     */
    public function sendByTemplate($to, $title, string $template = '', array $data = []): void
    {
        $message = (new TemplatedEmail())
            ->from(new Address($this->config['from']['address']))
            ->to(new Address($to))
            ->subject($title)
            ->htmlTemplate($template.'.html.twig')
            ->context($data);

        $loader = new FilesystemLoader(resource_path('emails'));
        $environment = new Environment($loader);
        (new BodyRenderer($environment))->render($message);

        $this->mailer->send($message);
    }
}
