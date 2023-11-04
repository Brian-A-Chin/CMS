<?php
class Mailer extends MailConfiguration {

    private string $template;
    public string $sender;
    private string $subject;
    private array $receivers;
    private array $parameters;
    private Render $twig;

    public function __construct($template, $sender, $subject, $receivers, $parameters) {
        $this->template = $template;
        $this->sender = $sender;
        $this->subject = $subject;
        $this->receivers = $receivers;
        $this->parameters = $parameters;
        $this->twig = new Render(ABSPATH .'templates/email');
    }

    public static function getDefaultAddresses($sender): bool|string {
        $senders = [
            'security' => MailConfiguration::$SECURITY_SENDER_ADDRESS,
            'NoReply' => MailConfiguration::$NO_REPLY_SENDER_ADDRESS
        ];
        return array_key_exists($sender, $senders) ? $senders[$sender] : false;
    }

    public function bulkSendEmail(): bool {
        $completedTemplate = $this->twig->gettemplate($this->template, $this->parameters);
        //Does not exit on single error.
        if ($completedTemplate != false) {
            foreach ($this->receivers as $recipient => $nameParts) {
                $fullName = implode(' ', $nameParts);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1];
                $email = new \SendGrid\Mail\Mail();
                $email->setFrom($this->sender, explode('@', $this->sender)[1]);
                $email->setsubject($this->subject);
                $email->addTo($recipient, $fullName);
                $email->addContent("text/plain", strip_tags($completedTemplate));
                $email->addContent('text/html', $completedTemplate);
                $sendgrid = new \SendGrid(MailConfiguration::$SENDGRID_API_KEY);
                try {
                    $response = $sendgrid->send($email);
                    if ($response->statusCode() != 202) {
                        BaseClass::logError([
                            'message' => 'Failed to send out email[' . $response->statusCode() . ']',
                            'exception' => $response->body()
                        ]);
                    }
                } catch (Exception $e) {
                    BaseClass::logError([
                        'message' => 'Failed to send out email',
                        'exception' => $e->getmessage()
                    ]);
                }

            }
        } else {
            BaseClass::logError([
                'message' => 'Email template does not exist',
                'exception' => 'template name:' . $this->template
            ]);
        }
        return true;

    }

    //Decrypt emails before sending
    public static function sendSingleEmail(string $template,array $templateParameters,string $fullName,string $sender,string $subject,string $recipient): bool {
        $twig = new Render(ABSPATH.'templates/email');
        $config = new ClientConfiguration();
        $templateParameters['clientConfig'] = $config->getArrayOfValues();
        $templateParameters['membershipPortalUrl'] = SITE_URL.'account/login';
        $completedTemplate = $twig->gettemplate($template, $templateParameters);
        //Does not exit on single error.
        if ($completedTemplate != false) {
            try {
                $email = new \SendGrid\Mail\Mail();
                $email->setFrom($sender, explode('@', $sender)[1]);
                $email->setsubject($subject);
                $email->addTo($recipient, $fullName);
                $email->addContent("text/plain", strip_tags($completedTemplate));
                $email->addContent('text/html', $completedTemplate);
                $sendgrid = new \SendGrid(MailConfiguration::$SENDGRID_API_KEY);
                $response = $sendgrid->send($email);
                if ($response->statusCode() != 202) {
                    BaseClass::logError([
                        'message' => 'Failed to send out email to '.$recipient.'[' . $response->statusCode() . ']',
                        'exception' => $response->body()
                    ]);
                }
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to send out email',
                    'exception' => $e->getmessage()
                ]);
            }
        } else {
            BaseClass::logError([
                'message' => 'Email template does not exist',
                'exception' => 'template name:' . $template
            ]);
        }
        return true;

    }

}