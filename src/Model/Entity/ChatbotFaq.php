<?php
// src/Model/Entity/ChatbotFaq.php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ChatbotFaq extends Entity
{
    protected array $_accessible = [
        'Question' => true,
        'Answer' => true,
        'SortOrder' => true,
        'IsActive' => true,
    ];
}
