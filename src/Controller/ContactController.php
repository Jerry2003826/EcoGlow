<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\ContactMessagesTable;
use App\Service\RecaptchaVerifier;
use Cake\Core\Configure;
use Cake\Http\Response;

/**
 * Public contact form controller.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class ContactController extends AppController
{
    /**
     * The contact messages table.
     *
     * @var \App\Model\Table\ContactMessagesTable
     */
    protected ContactMessagesTable $ContactMessages;

    /**
     * Controller initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Authentication->allowUnauthenticated(['index']);
        $this->ContactMessages = $this->fetchTable('ContactMessages');
    }

    /**
     * Display and process the contact form.
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $contactMessage = $this->ContactMessages->newEmptyEntity();

        if ($this->request->is('post')) {
            $verifier = new RecaptchaVerifier();
            $token = (string)$this->request->getData('g-recaptcha-response');
            $remoteIp = $this->request->clientIp();

            if (!$verifier->verify($token, $remoteIp)) {
                $this->Flash->error(__('Please complete the CAPTCHA to prove you are human.'));
                $contactMessage = $this->ContactMessages->patchEntity($contactMessage, $this->request->getData());
            } else {
                $contactMessage = $this->ContactMessages->patchEntity($contactMessage, $this->request->getData());
                if ($this->ContactMessages->save($contactMessage)) {
                    $this->Flash->success(__('Thank you! Your message has been sent. We will get back to you soon.'));

                    return $this->redirect(['action' => 'index']);
                }

                $this->Flash->error(__('Your message could not be sent. Please check the form and try again.'));
            }
        }

        $recaptchaSitekey = (string)Configure::read('Recaptcha.sitekey');
        $recaptchaEnabled = (bool)Configure::read('Recaptcha.enabled', true);
        $this->set(compact('contactMessage', 'recaptchaSitekey', 'recaptchaEnabled'));

        return null;
    }
}
