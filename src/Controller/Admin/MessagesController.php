<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class MessagesController extends AdminController
{
    protected function requirePermission(): void
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();

        if (
            !$permissionService->hasPermission(
                (int)$identity->getIdentifier(),
                'messages.manage'
            )
        ) {
            throw new ForbiddenException();
        }
    }

    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requirePermission();

        $messages = $this->fetchTable('ContactMessages')
            ->find()
            ->orderBy([
                'ContactMessages.is_read' => 'ASC',
                'ContactMessages.created' => 'DESC',
            ])
            ->all();

        $this->set(compact('messages'));
    }

    public function view(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requirePermission();

        $messagesTable = $this->fetchTable('ContactMessages');

        $message = $messagesTable->get($id);

        if (!(bool)$message->is_read) {
            $message->is_read = 1;
            $messagesTable->save($message);
        }

        $this->set(compact('message'));
    }
}
