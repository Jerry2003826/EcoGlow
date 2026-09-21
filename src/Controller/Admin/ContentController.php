<?php
// src/Controller/Admin/ContentController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\LandingBlock;
use App\Service\ImageUploadService;
use Psr\Http\Message\UploadedFileInterface;

class ContentController extends AdminController
{
    private const PAGE_KEY = 'home';

    /**
     * @var array<int, string>
     */
    private const BLOCK_TYPES = ['carousel', 'feature'];

    /**
     * Show every editable home page field and block.
     *
     * @return void
     */
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('content.manage');

        $texts = $this->fetchTable('LandingText')
            ->find()
            ->where(['PageKey' => self::PAGE_KEY])
            ->orderBy(['SortOrder' => 'ASC', 'TextId' => 'ASC'])
            ->all();

        $bannerTexts = [];
        $heroTexts = [];
        $productTexts = [];
        $serviceTexts = [];

        foreach ($texts as $text) {
            $key = (string)$text->TextKey;
            if (str_starts_with($key, 'hero.')) {
                $heroTexts[] = $text;
            } elseif (str_starts_with($key, 'products.')) {
                $productTexts[] = $text;
            } elseif (str_starts_with($key, 'service.')) {
                $serviceTexts[] = $text;
            } else {
                $bannerTexts[] = $text;
            }
        }

        $blocks = $this->fetchTable('LandingBlock')
            ->find()
            ->where(['PageKey' => self::PAGE_KEY])
            ->orderBy(['SortOrder' => 'ASC', 'BlockId' => 'ASC'])
            ->all();

        $carouselBlocks = [];
        $featureBlocks = [];

        foreach ($blocks as $block) {
            if ($block->BlockType === 'carousel') {
                $carouselBlocks[] = $block;
            } elseif ($block->BlockType === 'feature') {
                $featureBlocks[] = $block;
            }
        }

        $this->set(compact(
            'bannerTexts',
            'heroTexts',
            'productTexts',
            'serviceTexts',
            'carouselBlocks',
            'featureBlocks',
        ));
    }

    /**
     * Save existing landing text values only.
     *
     * @return \Cake\Http\Response|null
     */
    public function saveText()
    {
        $this->request->allowMethod(['post']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('content.manage');

        $submitted = $this->request->getData('text');
        if (!is_array($submitted)) {
            $this->Flash->error('No homepage text was submitted.');

            return $this->redirect(['action' => 'index']);
        }

        $table = $this->fetchTable('LandingText');
        $existing = $table
            ->find()
            ->where(['PageKey' => self::PAGE_KEY])
            ->all();

        $byKey = [];
        foreach ($existing as $row) {
            $byKey[(string)$row->TextKey] = $row;
        }

        $failed = false;
        foreach ($submitted as $key => $value) {
            if (!is_string($key) || !isset($byKey[$key])) {
                continue;
            }

            if (!is_scalar($value)) {
                $failed = true;
                continue;
            }

            $entity = $table->patchEntity($byKey[$key], [
                'TextValue' => (string)$value,
            ], [
                'fields' => ['TextValue'],
            ]);

            if (!$table->save($entity)) {
                $failed = true;
            }
        }

        if ($failed) {
            $this->Flash->error('Some homepage text could not be saved.');
        } else {
            $this->flashSaved('Homepage text saved.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Create a carousel or feature block.
     *
     * @return \Cake\Http\Response|null
     */
    public function addBlock()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('content.manage');

        $table = $this->fetchTable('LandingBlock');
        $block = $table->newEmptyEntity();
        $blockType = $this->requestedBlockType();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $upload = $data['image'] ?? null;
            unset(
                $data['image'],
                $data['PageKey'],
                $data['SortOrder'],
                $data['ImagePath'],
                $data['IsActive'],
            );

            if (!$this->isAllowedBlockType((string)($data['BlockType'] ?? ''))) {
                $this->Flash->error('Choose a valid homepage section.');
                $this->set(compact('block', 'blockType'));

                return;
            }

            $service = new ImageUploadService();
            $path = $service->store($upload, 'landing');
            if ($path === null) {
                $this->Flash->error($service->getLastError() ?? 'The image could not be saved.');
                $this->set(compact('block', 'blockType'));

                return;
            }

            $data['PageKey'] = self::PAGE_KEY;
            $data['ImagePath'] = $path;
            $data['IsActive'] = true;
            $data['SortOrder'] = $this->nextSortOrder((string)$data['BlockType']);

            $block = $table->patchEntity($block, $data, [
                'fields' => [
                    'PageKey',
                    'BlockType',
                    'SortOrder',
                    'Title',
                    'Description',
                    'ButtonLabel',
                    'ButtonUrl',
                    'ImagePath',
                    'AltText',
                    'IsActive',
                ],
            ]);

            if ($table->save($block)) {
                $this->flashSaved('Homepage item saved.');

                return $this->redirect(['action' => 'index']);
            }

            $service->delete($path, 'landing');
            $this->Flash->error('The homepage item could not be saved.');
            $blockType = (string)$block->BlockType;
        }

        $this->set(compact('block', 'blockType'));
    }

    /**
     * Edit an existing carousel or feature block.
     *
     * @param string|int $id Block id.
     * @return \Cake\Http\Response|null
     */
    public function editBlock(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('content.manage');

        $table = $this->fetchTable('LandingBlock');
        $block = $table->get((int)$id);
        $oldImage = (string)$block->ImagePath;

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();
            $upload = $data['image'] ?? null;
            unset(
                $data['image'],
                $data['PageKey'],
                $data['BlockType'],
                $data['SortOrder'],
                $data['ImagePath'],
                $data['IsActive'],
            );

            $service = new ImageUploadService();
            $newPath = null;

            if ($this->hasImageUpload($upload)) {
                $newPath = $service->store($upload, 'landing');
                if ($newPath === null) {
                    $this->Flash->error($service->getLastError() ?? 'The image could not be saved.');
                    $this->set(compact('block'));

                    return;
                }

                $data['ImagePath'] = $newPath;
            }

            $fields = [
                'Title',
                'Description',
                'ButtonLabel',
                'ButtonUrl',
                'AltText',
            ];
            if ($newPath !== null) {
                $fields[] = 'ImagePath';
            }

            $block = $table->patchEntity($block, $data, ['fields' => $fields]);

            if ($table->save($block)) {
                if ($newPath !== null && $newPath !== $oldImage) {
                    $this->deleteUnreferencedImage($oldImage);
                }

                $this->flashSaved('Homepage item updated.');

                return $this->redirect(['action' => 'index']);
            }

            if ($newPath !== null) {
                $service->delete($newPath, 'landing');
            }

            $this->Flash->error('The homepage item could not be updated.');
        }

        $this->set(compact('block'));
    }

    /**
     * Delete a block and remove its image when nothing else uses it.
     *
     * @param string|int $id Block id.
     * @return \Cake\Http\Response|null
     */
    public function deleteBlock(int|string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('content.manage');

        $table = $this->fetchTable('LandingBlock');
        $block = $table->get((int)$id);
        $imagePath = (string)$block->ImagePath;

        if ($table->delete($block)) {
            $this->deleteUnreferencedImage($imagePath);
            $this->flashSaved('Homepage item deleted.');
        } else {
            $this->Flash->error('The homepage item could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Reorder a block within the same type.
     *
     * @param string|int $id Block id.
     * @param string $direction up, down, top, or bottom.
     * @return \Cake\Http\Response|null
     */
    public function moveBlock(int|string $id, string $direction)
    {
        $this->request->allowMethod(['post']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('content.manage');

        $allowedDirections = ['up', 'down', 'top', 'bottom'];
        if (!in_array($direction, $allowedDirections, true)) {
            $this->Flash->error('That move is not supported.');

            return $this->redirect(['action' => 'index']);
        }

        $table = $this->fetchTable('LandingBlock');
        $block = $table->get((int)$id);
        $siblings = $table
            ->find()
            ->where([
                'PageKey' => $block->PageKey,
                'BlockType' => $block->BlockType,
            ])
            ->orderBy(['SortOrder' => 'ASC', 'BlockId' => 'ASC'])
            ->all()
            ->toList();

        $index = null;
        foreach ($siblings as $position => $sibling) {
            if ((int)$sibling->BlockId === (int)$block->BlockId) {
                $index = $position;
                break;
            }
        }

        if ($index === null) {
            return $this->redirect(['action' => 'index']);
        }

        $lastIndex = count($siblings) - 1;
        $moved = false;

        if ($direction === 'up') {
            if ($index > 0) {
                $moved = $this->swapSortOrder($siblings[$index], $siblings[$index - 1]);
            }
        } elseif ($direction === 'down') {
            if ($index < $lastIndex) {
                $moved = $this->swapSortOrder($siblings[$index], $siblings[$index + 1]);
            }
        } elseif ($direction === 'top') {
            if ($index > 0) {
                $block->SortOrder = (int)$siblings[0]->SortOrder - 1;
                $moved = (bool)$table->save($block);
            }
        } elseif ($direction === 'bottom') {
            if ($index < $lastIndex) {
                $block->SortOrder = (int)$siblings[$lastIndex]->SortOrder + 1;
                $moved = (bool)$table->save($block);
            }
        }

        if ($moved) {
            $this->flashSaved('Homepage item order updated.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Toggle whether a block is visible on the home page.
     *
     * @param string|int $id Block id.
     * @return \Cake\Http\Response|null
     */
    public function toggleBlock(int|string $id)
    {
        $this->request->allowMethod(['post']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('content.manage');

        $table = $this->fetchTable('LandingBlock');
        $block = $table->get((int)$id);
        $block->IsActive = !$block->IsActive;

        if ($table->save($block)) {
            $this->flashSaved($block->IsActive ? 'Homepage item is now visible.' : 'Homepage item is now hidden.');
        } else {
            $this->Flash->error('The homepage item could not be updated.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Flash a success message that also links to the storefront.
     *
     * @param string $message Success message.
     * @return void
     */
    private function flashSaved(string $message): void
    {
        $this->Flash->success(
            h($message) . ' <a href="/" target="_blank" rel="noopener noreferrer">View home page</a>',
            ['escape' => false],
        );
    }

    /**
     * Read a valid block type from the form or query string.
     *
     * @return string
     */
    private function requestedBlockType(): string
    {
        $fromData = (string)$this->request->getData('BlockType', '');
        if ($this->isAllowedBlockType($fromData)) {
            return $fromData;
        }

        $fromQuery = (string)$this->request->getQuery('BlockType', '');
        if ($this->isAllowedBlockType($fromQuery)) {
            return $fromQuery;
        }

        return '';
    }

    /**
     * Whether the submitted block type is allowed.
     *
     * @param string $blockType Candidate type.
     * @return bool
     */
    private function isAllowedBlockType(string $blockType): bool
    {
        return in_array($blockType, self::BLOCK_TYPES, true);
    }

    /**
     * Next SortOrder for a block type on the home page.
     *
     * @param string $blockType carousel or feature.
     * @return int
     */
    private function nextSortOrder(string $blockType): int
    {
        $table = $this->fetchTable('LandingBlock');
        $query = $table->find();
        $row = $query
            ->select(['max_order' => $query->func()->max('SortOrder')])
            ->where([
                'PageKey' => self::PAGE_KEY,
                'BlockType' => $blockType,
            ])
            ->first();

        return (int)($row?->max_order ?? 0) + 1;
    }

    /**
     * Swap SortOrder values for two blocks.
     *
     * @param \App\Model\Entity\LandingBlock $left First block.
     * @param \App\Model\Entity\LandingBlock $right Second block.
     * @return bool
     */
    private function swapSortOrder(LandingBlock $left, LandingBlock $right): bool
    {
        $table = $this->fetchTable('LandingBlock');
        $leftOrder = (int)$left->SortOrder;
        $rightOrder = (int)$right->SortOrder;
        $left->SortOrder = $rightOrder;
        $right->SortOrder = $leftOrder;

        return (bool)$table->save($left) && (bool)$table->save($right);
    }

    /**
     * Whether the request includes an attempted image upload.
     *
     * @param mixed $upload Uploaded file payload.
     * @return bool
     */
    private function hasImageUpload(mixed $upload): bool
    {
        if ($upload instanceof UploadedFileInterface) {
            return $upload->getError() !== UPLOAD_ERR_NO_FILE;
        }

        return $upload !== null && $upload !== '';
    }

    /**
     * Delete a stored landing image when no other row still points at it.
     *
     * Seed images stay on disk. Only ImageUploadService filenames are removed.
     *
     * @param string $storedPath Relative path such as landing/abc.jpg.
     * @return void
     */
    private function deleteUnreferencedImage(string $storedPath): void
    {
        if ($storedPath === '' || !$this->isGeneratedUploadPath($storedPath)) {
            return;
        }

        if ($this->imagePathIsReferenced($storedPath)) {
            return;
        }

        (new ImageUploadService())->delete($storedPath, 'landing');
    }

    /**
     * Whether another landing row still uses this image path.
     *
     * @param string $imagePath Relative image path.
     * @return bool
     */
    private function imagePathIsReferenced(string $imagePath): bool
    {
        $blockCount = $this->fetchTable('LandingBlock')
            ->find()
            ->where(['ImagePath' => $imagePath])
            ->count();

        if ($blockCount > 0) {
            return true;
        }

        return $this->fetchTable('LandingText')
            ->find()
            ->where(['TextValue' => $imagePath])
            ->count() > 0;
    }

    /**
     * Whether a path looks like a file created by ImageUploadService.
     *
     * @param string $storedPath Relative image path.
     * @return bool
     */
    private function isGeneratedUploadPath(string $storedPath): bool
    {
        if (!str_starts_with($storedPath, 'landing/')) {
            return false;
        }

        $filename = substr($storedPath, strlen('landing/'));

        return (bool)preg_match('/^[a-f0-9]{32}\.(jpe?g|png|webp)$/', $filename);
    }
}
