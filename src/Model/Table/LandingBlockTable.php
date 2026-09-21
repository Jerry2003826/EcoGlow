<?php
// src/Model/Table/LandingBlockTable.php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\LandingBlock;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class LandingBlockTable extends Table
{
    /**
     * Configure the LandingBlock table mapping.
     *
     * @param array<string, mixed> $config Table config.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('LandingBlock');
        $this->setPrimaryKey('BlockId');
        $this->setDisplayField('Title');
        $this->setEntityClass(LandingBlock::class);
    }

    /**
     * Default validation rules for landing blocks.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('PageKey')
            ->maxLength('PageKey', 50)
            ->notEmptyString('PageKey');

        $validator
            ->scalar('BlockType')
            ->requirePresence('BlockType', 'create')
            ->notEmptyString('BlockType')
            ->inList('BlockType', ['carousel', 'feature'], 'Block type must be carousel or feature.');

        $validator
            ->integer('SortOrder')
            ->notEmptyString('SortOrder');

        $validator
            ->scalar('Title')
            ->maxLength('Title', 200)
            ->requirePresence('Title', 'create')
            ->notEmptyString('Title');

        $validator
            ->scalar('Description')
            ->requirePresence('Description', 'create')
            ->notEmptyString('Description');

        $validator
            ->scalar('ButtonLabel')
            ->maxLength('ButtonLabel', 100)
            ->allowEmptyString('ButtonLabel');

        $validator
            ->scalar('ButtonUrl')
            ->maxLength('ButtonUrl', 255)
            ->allowEmptyString('ButtonUrl')
            ->add('ButtonUrl', 'safeUrl', [
                'rule' => fn($value) => $this->isSafeButtonUrl($value),
                'message' => 'Enter a site-relative path, in-page anchor, or http(s) URL.',
            ]);

        $validator
            ->scalar('ImagePath')
            ->maxLength('ImagePath', 255)
            ->requirePresence('ImagePath', 'create')
            ->notEmptyString('ImagePath');

        $validator
            ->scalar('AltText')
            ->maxLength('AltText', 255)
            ->requirePresence('AltText', 'create')
            ->notEmptyString('AltText');

        $validator
            ->boolean('IsActive')
            ->allowEmptyString('IsActive');

        return $validator;
    }

    /**
     * Allow only empty values, site paths, in-page anchors, or http(s) URLs.
     *
     * @param mixed $value Submitted button URL.
     * @return bool
     */
    protected function isSafeButtonUrl(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        $url = trim($value);
        if ($url === '') {
            return true;
        }

        $lower = strtolower($url);
        foreach (['javascript:', 'data:', 'vbscript:'] as $scheme) {
            if (str_starts_with($lower, $scheme)) {
                return false;
            }
        }

        if (str_starts_with($url, '//')) {
            return false;
        }

        return str_starts_with($url, '/')
            || str_starts_with($url, '#')
            || str_starts_with($lower, 'http://')
            || str_starts_with($lower, 'https://');
    }
}
