<?php
// src/Service/LandingContentService.php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\LandingBlock;
use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\TableRegistry;
use Throwable;

class LandingContentService
{
    /**
     * Hard-coded home page copy used when the CMS tables are missing or empty.
     *
     * @var array<string, string>
     */
    private const DEFAULT_TEXTS = [
        'carousel.eyebrow' => 'Featured collection',
        'hero.eyebrow' => 'Lighting for everyday living',
        'hero.title' => 'All things Lighting.',
        'hero.body1' => 'Eco Glow Lighting offers modern lighting fixtures'
            . ' and smart home solutions designed to make your home feel brighter and better.',
        'hero.body2' => 'Enquire about our installation and repair services,'
            . ' or explore our featured lighting range below.',
        'hero.button_primary_label' => 'Shop all Lighting',
        'hero.button_primary_url' => '/products',
        'hero.button_secondary_label' => 'Make an Enquiry',
        'hero.button_secondary_url' => '/enquiries/add',
        'hero.image' => 'landing/pendants.jpg',
        'hero.image_alt' => 'Pendant lights in a modern interior',
        'products.eyebrow' => 'Our Product Range',
        'products.title' => 'Lighting for every space',
        'products.description' => 'Explore warm, practical and energy-efficient lighting'
            . ' selected for modern Australian homes.',
        'service.eyebrow' => 'Installation and repairs',
        'service.title' => 'Need help with your lighting?',
        'service.body' => 'Talk to us about product selection, installation,'
            . ' replacements and lighting repairs for your home.',
        'service.button_label' => 'Contact us',
        'service.button_url' => '/enquiries/add',
    ];

    private LocatorInterface $tables;

    /**
     * @param \Cake\ORM\Locator\LocatorInterface|null $tables Table locator. Defaults to the global locator.
     */
    public function __construct(?LocatorInterface $tables = null)
    {
        $this->tables = $tables ?? TableRegistry::getTableLocator();
    }

    /**
     * Return homepage text keyed by TextKey, falling back to the original copy.
     *
     * @param string $pageKey Landing page key.
     * @return array<string, string>
     */
    public function textMap(string $pageKey = 'home'): array
    {
        $defaults = self::DEFAULT_TEXTS;

        try {
            $rows = $this->tables->get('LandingText')
                ->find()
                ->where(['PageKey' => $pageKey])
                ->all();

            if ($rows->count() === 0) {
                return $defaults;
            }

            $map = $defaults;
            foreach ($rows as $row) {
                $map[(string)$row->TextKey] = (string)$row->TextValue;
            }

            return $map;
        } catch (Throwable) {
            return $defaults;
        }
    }

    /**
     * Return ordered, active blocks for a section, or the original hard-coded cards.
     *
     * @param string $blockType carousel or feature.
     * @param string $pageKey Landing page key.
     * @return array<int, \App\Model\Entity\LandingBlock>
     */
    public function blocks(string $blockType, string $pageKey = 'home'): array
    {
        try {
            $table = $this->tables->get('LandingBlock');
            $existing = $table
                ->find()
                ->where([
                    'PageKey' => $pageKey,
                    'BlockType' => $blockType,
                ])
                ->count();

            if ($existing === 0) {
                return $this->defaultBlocks($blockType);
            }

            return $table
                ->find()
                ->where([
                    'PageKey' => $pageKey,
                    'BlockType' => $blockType,
                    'IsActive' => 1,
                ])
                ->orderBy(['SortOrder' => 'ASC', 'BlockId' => 'ASC'])
                ->all()
                ->toList();
        } catch (Throwable) {
            return $this->defaultBlocks($blockType);
        }
    }

    /**
     * Build default block entities so the template can use one data shape.
     *
     * @param string $blockType carousel or feature.
     * @return array<int, \App\Model\Entity\LandingBlock>
     */
    private function defaultBlocks(string $blockType): array
    {
        $rows = match ($blockType) {
            'carousel' => $this->defaultCarouselRows(),
            'feature' => $this->defaultFeatureRows(),
            default => [],
        };

        $blocks = [];
        foreach ($rows as $row) {
            $blocks[] = new LandingBlock($row, ['guard' => false]);
        }

        return $blocks;
    }

    /**
     * Original homepage carousel slides.
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultCarouselRows(): array
    {
        return [
            [
                'PageKey' => 'home',
                'BlockType' => 'carousel',
                'SortOrder' => 1,
                'Title' => 'A brighter space. A warmer welcome.',
                'Description' => 'Eco Glow Lighting offers a range of modern lighting solutions'
                    . ' for your home, including pendant, wall, table and floor lighting.',
                'ButtonLabel' => 'Explore lighting',
                'ButtonUrl' => '#shop',
                'ImagePath' => 'landing/interior.jpg',
                'AltText' => 'Clear pendant lights hanging in a row',
                'IsActive' => true,
            ],
            [
                'PageKey' => 'home',
                'BlockType' => 'carousel',
                'SortOrder' => 2,
                'Title' => 'Great lighting starts with a conversation',
                'Description' => 'Get in touch with our team to discuss your lighting needs.',
                'ButtonLabel' => 'Talk to our team',
                'ButtonUrl' => '/enquiries/add',
                'ImagePath' => 'landing/opal-lamp.jpg',
                'AltText' => 'Opal table lamp on a side table in a living room',
                'IsActive' => true,
            ],
            [
                'PageKey' => 'home',
                'BlockType' => 'carousel',
                'SortOrder' => 3,
                'Title' => 'Lighting for every space',
                'Description' => 'Explore our range of pendant, wall, table and floor lighting for your home.',
                'ButtonLabel' => 'View our products',
                'ButtonUrl' => '/products',
                'ImagePath' => 'landing/pendants.jpg',
                'AltText' => 'Pendant lights in a modern interior',
                'IsActive' => true,
            ],
        ];
    }

    /**
     * Original homepage product-range cards.
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultFeatureRows(): array
    {
        return [
            [
                'PageKey' => 'home',
                'BlockType' => 'feature',
                'SortOrder' => 1,
                'Title' => 'Pendant Lights',
                'Description' => 'Ceiling-mounted and pendant lighting for indoor spaces.',
                'ButtonLabel' => 'Ask about this light',
                'ButtonUrl' => '/enquiries/add',
                'ImagePath' => 'landing/interior.jpg',
                'AltText' => 'Oak pendant light above a dining table',
                'IsActive' => true,
            ],
            [
                'PageKey' => 'home',
                'BlockType' => 'feature',
                'SortOrder' => 2,
                'Title' => 'Table Lamps',
                'Description' => 'A simple table lamp with a natural linen shade,'
                    . ' ideal for bedside tables and reading corners.',
                'ButtonLabel' => 'Ask about this light',
                'ButtonUrl' => '/enquiries/add',
                'ImagePath' => 'landing/table-lamp.jpg',
                'AltText' => 'Linen table lamp on a side table',
                'IsActive' => true,
            ],
            [
                'PageKey' => 'home',
                'BlockType' => 'feature',
                'SortOrder' => 3,
                'Title' => 'Wall Lights',
                'Description' => 'Wall-mounted lighting for indoor and decorative use.',
                'ButtonLabel' => 'Ask about this light',
                'ButtonUrl' => '/enquiries/add',
                'ImagePath' => 'landing/wall-light.jpg',
                'AltText' => 'Brass wall light mounted on an interior wall',
                'IsActive' => true,
            ],
            [
                'PageKey' => 'home',
                'BlockType' => 'feature',
                'SortOrder' => 4,
                'Title' => 'Floor Lamps',
                'Description' => 'Freestanding lighting for living and reading areas.',
                'ButtonLabel' => 'Ask about this light',
                'ButtonUrl' => '/enquiries/add',
                'ImagePath' => 'landing/floor-lamp.jpg',
                'AltText' => 'Dome floor lamp beside an armchair',
                'IsActive' => true,
            ],
            [
                'PageKey' => 'home',
                'BlockType' => 'feature',
                'SortOrder' => 5,
                'Title' => 'Outdoor Lights',
                'Description' => 'Weather-resistant lighting for gardens and outdoor areas.',
                'ButtonLabel' => 'Ask about this light',
                'ButtonUrl' => '/enquiries/add',
                'ImagePath' => 'landing/outdoor-lighting.jpg',
                'AltText' => 'Outdoor lights in a garden setting',
                'IsActive' => true,
            ],
            [
                'PageKey' => 'home',
                'BlockType' => 'feature',
                'SortOrder' => 6,
                'Title' => 'Smart Lights',
                'Description' => 'An energy-efficient smart globe with adjustable brightness'
                    . ' for a flexible home lighting setup.',
                'ButtonLabel' => 'Ask about this light',
                'ButtonUrl' => '/enquiries/add',
                'ImagePath' => 'landing/globe.jpg',
                'AltText' => 'Smart LED light globe',
                'IsActive' => true,
            ],
        ];
    }
}
