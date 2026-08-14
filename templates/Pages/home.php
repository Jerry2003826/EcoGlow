<?php
/**
 * Eco Glow Lighting home page — warm-earth storefront theme.
 *
 * The catalogue is not modelled yet, so the collections and best-sellers below
 * are local placeholder arrays, exactly as the category list was before. When
 * the products table lands these two loops become the only things that change.
 *
 * `image` names a file in webroot/img/products and replaces the `icon` key the
 * line-art marks used to be chosen by: the client's board is photographic
 * throughout, and a monochrome lamp outline read as a placeholder rather than as
 * a product. It is still the shape a column would take — a filename resolved at
 * render time — so the swap costs the future controller nothing.
 *
 * `text` states materials, colour temperature and dimming type instead of
 * adjectives, after the Lighting Collective page on the board. Nothing here
 * claims a certification, a licence number or a street address; what is
 * asserted is what the fittings are made of and how they behave on a circuit.
 *
 * On layout: like items are the same size and sit on the same baselines. Both of
 * the real storefronts on the client's board — Nook Collections and Lighting
 * Collective — lay their product and collection grids out regularly, because a
 * visitor comparing six prices across a row should not also have to work out why
 * one card is bigger. The unequal, overlapping arrangements on the board belong
 * to LUMINOTTI's editorial displays, not to its product lists.
 *
 * The rhythm therefore comes from between the bands rather than from inside
 * them: a full-bleed photograph, then a greige band, then a warm white statement
 * band, then a charcoal services band, with the text-to-grid ratio changing from
 * one to the next.
 *
 * Each band also has to say something the others do not. `$steps` used to be
 * rendered twice — as the numbered list in the services band and again as a
 * timeline in the about band, same four titles, near enough the same sentences —
 * which is the clearest sign a column was filled rather than written. The steps
 * now appear once, in the services band that they describe, and the about band
 * carries `$materials` instead: the five things every fitting on the site is
 * made from, and what each one is doing there.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Modern Lighting & Smart Home Illumination');

$collections = [
    [
        'image' => 'corva-ceiling-disc.webp',
        'name' => 'LED Ceiling Lights',
        'text' => 'Flush and semi-flush discs in opal glass and brushed brass. 3000K, dimmable on trailing-edge.',
    ],
    [
        'image' => 'marlow-floor-lamp.webp',
        'name' => 'Ambient Floor Lamps',
        'text' => 'Turned oak columns under undyed linen shades. E27 fittings on a 2 m cable with an in-line rotary dimmer.',
    ],
    [
        'image' => 'aura-smart-bulbs.webp',
        'name' => 'Smart Bulbs',
        'text' => 'E27 and E14 globes, tunable 2200–6500K. Dimmed from an app or the wall dial, no hub needed.',
    ],
    [
        'image' => 'fernway-solar-path.webp',
        'name' => 'Outdoor Solar Lights',
        'text' => 'Powder-coated aluminium spikes and bollards, IP65, 3000K, on dusk-to-dawn sensors.',
    ],
    [
        'image' => 'linen-drum-shade.webp',
        'name' => 'Decorative Accessories',
        'text' => 'Undyed linen drum shades from 30 to 50 cm, braided cloth flex and trailing-edge rotary dimmers.',
    ],
    [
        'image' => 'ashby-twin-sconce.webp',
        'name' => 'Wall Sconces',
        'text' => 'Twin-arm brass frames and opal glass domes. E14 fittings, wired to a box or plug-in on a cloth flex.',
    ],
];

$bestSellers = [
    [
        'image' => 'marlow-floor-lamp.webp',
        'alt' => 'Marlow floor lamp lit against a plaster wall, oak column under a linen drum shade',
        'name' => 'Marlow Floor Lamp',
        'meta' => 'Turned oak, linen shade, 1.45 m',
        'price' => 249.00,
        'flag' => 'New',
        'swatches' => [['Oak', '#C9BCA9'], ['Charcoal', '#2F2E2C'], ['Terracotta', '#E2925E']],
    ],
    [
        'image' => 'halden-pendant.webp',
        'alt' => 'Halden pendant hanging on a slim brass stem, its opal glass globe lit',
        'name' => 'Halden Pendant',
        'meta' => 'Opal glass globe, 20 cm, E27',
        'price' => 189.00,
        'flag' => null,
        'swatches' => [['Opal', '#E2DED2'], ['Forest', '#124C24']],
    ],
    [
        'image' => 'aura-smart-bulbs.webp',
        'alt' => 'Four Aura globes laid in a row on brass screw bases, two of them lit',
        'name' => 'Aura Smart Bulb Set',
        'meta' => 'Four E27 globes, 2200–6500K',
        'price' => 79.00,
        'flag' => 'Best seller',
        'swatches' => [['Warm white', '#FBF9F5']],
    ],
    [
        'image' => 'fernway-solar-path.webp',
        'alt' => 'Three Fernway path lights spiked along a gravel path, lit at dusk',
        'name' => 'Fernway Solar Path Light',
        'meta' => 'Set of six spikes, IP65, 3000K',
        'price' => 129.00,
        'flag' => null,
        'swatches' => [['Charcoal', '#2F2E2C'], ['Sand', '#C9BCA9']],
    ],
];

$steps = [
    [
        'title' => 'Consultation',
        'text' => 'A room-by-room list: what is on each circuit now, what it should run at, and where a dimmer has to go.',
    ],
    [
        'title' => 'On-site assessment',
        'text' => 'We measure ceiling voids and cut-outs, read the switchboard, and mark sconce and downlight positions.',
    ],
    [
        'title' => 'Installation & setup',
        'text' => 'Licensed electricians mount and wire each fitting, then set the dimmer curve and the smart-globe scenes.',
    ],
    [
        'title' => 'Aftercare & repairs',
        'text' => 'Re-lamping, driver and transformer swaps, buzzing dimmers, and smart-globe firmware.',
    ],
];

/* What the about band shows instead of a second copy of $steps. Each entry
   states where the material is used and how it behaves — a property of the
   material or of the finish on it, which is checkable and stays true. Nothing
   here is a claim about the business. */
$materials = [
    [
        'image' => 'oak.webp',
        'name' => 'Turned oak',
        'text' => 'Floor-lamp columns and side-table bases, turned solid and finished in oil rather than lacquer, so the grain stays open and a scuff rubs back instead of chipping.',
    ],
    [
        'image' => 'linen.webp',
        'name' => 'Undyed linen',
        'text' => 'Drum and cone shades from 30 to 50 cm. Left undyed because a dyed weave tints whatever passes through it, and the globe behind it is already set at 2700–3000K.',
    ],
    [
        'image' => 'opal.webp',
        'name' => 'Opal glass',
        'text' => 'Pendant globes, sconce domes and flush ceiling discs. Opal scatters across the whole surface rather than leaving one bright spot, which keeps a bare LED out of your eye line.',
    ],
    [
        'image' => 'brass.webp',
        'name' => 'Brushed brass',
        'text' => 'Stems, canopies and sconce arms. Left unlacquered, so it darkens slowly and evenly instead of wearing through a clear coat in the places a hand touches it.',
    ],
    [
        'image' => 'powder.webp',
        'name' => 'Powder-coated aluminium',
        'text' => 'Outdoor spikes, bollards and IP65 housings. The coating is baked on and is the weatherproofing itself, so there is no paint film to lift at an edge.',
    ],
];

$shopUrl = $this->Url->build('/shop');
$productUrl = $this->Url->build('/shop/product');
$contactUrl = $this->Url->build('/contact');
?>
<?php
/* The photograph runs edge to edge: no container, no gutter, no radius. It is
   the first thing on the page and the only full-bleed element on the site, which
   is what makes it read as a photograph of a room rather than as an illustration
   in a box.

   From 992px the copy is laid over its left side. The panel behind that copy is a
   flat 0.94 alpha of the warm white ground and it bleeds off the left edge on a
   pseudo-element, so every glyph sits on exactly that alpha whatever the
   photograph is doing underneath — the contrast cannot drift as the crop changes
   with the viewport. Below 992px the copy stacks under the photograph on the
   page ground instead. */
?>
<section class="hero-eg">
    <div class="hero-collage reveal">
        <figure class="hero-shot">
            <?= $this->Html->image('hero-interior.webp', [
                'alt' => 'A lit floor lamp beside a boucle armchair and an oak side table in a bare, sunlit room',
                'width' => 1024,
                'height' => 683,
                'fetchpriority' => 'high',
                'decoding' => 'async',
            ]) ?>
        </figure>

        <div class="hero-overlay">
            <div class="container">
                <div class="hero-copy">
                    <span class="eg-eyebrow">Melbourne &mdash; supply, install &amp; repair</span>
                    <h1 class="hero-title">Light that feels like home.</h1>
                    <p class="hero-lead mt-3">
                        Oak, linen, opal glass and brushed brass, specified at 2700&ndash;3000K so rooms
                        stay warm after dark. Our licensed electricians mount, wire and repair
                        everything on these pages.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a class="btn btn-eg-primary" href="<?= h($shopUrl) ?>">
                            Shop collections
                            <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                        </a>
                        <a class="btn btn-eg-ghost" href="<?= h($contactUrl) ?>">Book a consultation</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <p class="hero-caption reveal">
            Shown: Marlow Floor Lamp in oiled oak with a 45 cm linen shade, on a 9 W 2700K globe.
        </p>
    </div>
</section>

<section class="section-eg eg-band-alt" id="collections">
    <div class="container">
        <?php
        /* Left-aligned rather than centred, with the "view all" opposite it on
           the same line: it puts the heading over the first tile's left edge and
           keeps the band from reading as a poster. */
        ?>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 mb-lg-5 reveal">
            <div>
                <span class="eg-eyebrow">Our range</span>
                <h2 class="section-title">Shop by collection</h2>
                <p class="section-lead mt-3 mb-0">
                    Six collections, all specified at 2700&ndash;3000K and dimmable on a trailing-edge
                    circuit, so a ceiling disc and a bedside sconce behave the same way on the wall dial.
                </p>
            </div>
            <a class="btn btn-eg-ghost btn-sm" href="<?= h($shopUrl) ?>">
                All lighting
                <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
            </a>
        </div>

        <?php
        /* Six tiles, one size, three to a row. The photographs are decorative:
           each tile's own name and description already say everything the picture
           says, and a described copy inside the link would only lengthen the
           link's accessible name for no new information. The whole tile is the
           link, so every target clears 44px several times over. */
        ?>
        <div class="eg-tile-grid reveal">
            <?php foreach ($collections as $collection) : ?>
                <a class="category-tile" href="<?= h($this->Url->build('/shop', ['?' => ['category' => $collection['name']]])) ?>">
                    <span class="tile-media">
                        <?= $this->Html->image('products/' . $collection['image'], [
                            'alt' => '',
                            'width' => 800,
                            'height' => 800,
                            'loading' => 'lazy',
                            'decoding' => 'async',
                        ]) ?>
                    </span>
                    <span class="tile-body">
                        <span class="tile-name"><?= h($collection['name']) ?></span>
                        <span class="tile-text"><?= h($collection['text']) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
/* The board's centred statement, short rules above and below. The grainy wash
   is kept here rather than behind a photograph: laid over a real product shot it
   only made the photograph look dirty, and on a plain ground it is what the
   board's cover actually does. */
?>
<section class="section-eg eg-manifesto">
    <span class="eg-wash" aria-hidden="true"></span>
    <div class="container">
        <p class="eg-manifesto-line reveal">
            We work from a short list of materials &mdash; turned oak, undyed linen, opal glass,
            brushed brass, powder-coated aluminium &mdash; and wire every fitting ourselves.
        </p>
        <div class="text-center reveal">
            <a class="btn btn-eg-ghost btn-sm" href="<?= h($contactUrl) ?>">Talk to us about a room</a>
        </div>
    </div>
</section>

<section class="section-eg" id="bestsellers">
    <div class="container">
        <?php
        /* The heading, its lead and the collection shortcuts sit in a column of
           their own to the left, and the four products in a regular two-by-two
           grid to the right. The asymmetry is between the text column and the
           grid — the products themselves are the same size on the same
           baselines, because they are four things a visitor is comparing. */
        ?>
        <div class="eg-lineup">
            <div class="eg-lineup-intro reveal">
                <span class="eg-eyebrow">Loved this season</span>
                <h2 class="section-title">Best sellers</h2>
                <p class="section-lead mt-3">
                    The four we re-order most often. Fitting, colour temperature and shade size
                    are on every card, so nothing has to be guessed from the photograph.
                </p>
                <a class="btn btn-eg-ghost btn-sm mt-2" href="<?= h($shopUrl) ?>">
                    View all
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>

                <div class="eg-chip-row mt-4">
                    <?php
                    /* These carry a ?category= that webroot/js/glow.js reads on
                       /shop, so a chip now applies the filter it names instead
                       of dropping the visitor on the unfiltered catalogue. */
                    ?>
                    <?php foreach (array_slice($collections, 0, 3) as $collection) : ?>
                        <a class="eg-chip" href="<?= h($this->Url->build('/shop', ['?' => ['category' => $collection['name']]])) ?>">
                            <?= h($collection['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ($bestSellers as $index => $product) : ?>
                <div class="eg-lineup-cell reveal" data-reveal-step="<?= $index ?>">
                    <a class="product-card" href="<?= h($productUrl) ?>">
                        <span class="product-media">
                            <?php if ($product['flag'] !== null) : ?>
                                <span class="product-flag"><?= h($product['flag']) ?></span>
                            <?php endif; ?>
                            <?= $this->Html->image('products/' . $product['image'], [
                                'alt' => $product['alt'],
                                'width' => 800,
                                'height' => 800,
                                'loading' => 'lazy',
                                'decoding' => 'async',
                            ]) ?>
                        </span>
                        <span class="product-body">
                            <span class="product-name"><?= h($product['name']) ?></span>
                            <span class="product-meta"><?= h($product['meta']) ?></span>
                            <span class="product-price">$<?= h(number_format($product['price'], 2)) ?></span>
                            <span class="product-swatches">
                                <?php foreach ($product['swatches'] as [$swatchName, $swatchHex]) : ?>
                                    <span class="swatch" style="background: <?= h($swatchHex) ?>;" aria-hidden="true"></span>
                                <?php endforeach; ?>
                                <span class="visually-hidden">
                                    Available in <?= h(implode(', ', array_column($product['swatches'], 0))) ?>
                                </span>
                            </span>
                        </span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-eg eg-band-dark" id="services">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-5 reveal">
                <span class="eg-eyebrow">Services</span>
                <h2 class="section-title mb-3">Installation &amp; repair</h2>
                <p>
                    Our licensed electricians mount, wire and commission everything on these
                    pages: trailing-edge dimmer circuits, 90 mm downlight cut-outs, sconce
                    boxes and outdoor spurs. Re-lamping and repairs too.
                </p>
                <div class="mt-4">
                    <?php foreach ($steps as $index => $step) : ?>
                        <div class="service-step">
                            <span class="step-no"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <div>
                                <h3><?= h($step['title']) ?></h3>
                                <p><?= h($step['text']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a class="btn btn-eg-primary mt-4" href="<?= h($contactUrl) ?>">
                    Book a service
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>
            </div>
            <div class="col-lg-7 reveal" data-reveal-step="2">
                <?php
                /* Two photographs of one room. This used to be a pair of CSS
                   gradients with a charcoal wash clipped over half of them,
                   under a caption naming a specific job — one 4 m living room
                   on a single batten holder, then on four dimmable downlights
                   and a floor lamp. A caption that precise over two abstract
                   rectangles reads worse than no demo at all.

                   The "after" is the ground and the "before" is clipped over
                   it, so the divider at 0% is the finished room and at 100% the
                   original, which is the direction its aria-valuenow already
                   described. Both are photographs and both carry an alt that
                   says what the lighting is doing, because the difference
                   between them is the content of this block, not decoration —
                   read in order the two alts describe the comparison on their
                   own, for anyone who cannot work the divider. */
                ?>
                <div class="compare-band" data-compare style="--split: 50%;">
                    <?= $this->Html->image('after-lighting.webp', [
                        'class' => 'compare-shot',
                        'alt' => 'After: four dimmable downlights wash overlapping pools of warm light down the wall of a living room corner, with a linen-shaded floor lamp lighting the armchair from the right.',
                        'width' => 1024,
                        'height' => 683,
                        'loading' => 'lazy',
                        'decoding' => 'async',
                    ]) ?>
                    <?= $this->Html->image('before-lighting.webp', [
                        'class' => 'compare-shot compare-shot-before',
                        'alt' => 'Before: the same corner on one bare ceiling fitting — flat grey light, no shape on the wall, and the armchair and the corner behind it left in shadow.',
                        'width' => 1024,
                        'height' => 683,
                        'loading' => 'lazy',
                        'decoding' => 'async',
                    ]) ?>
                    <span class="compare-tag compare-tag-dark">Before</span>
                    <span class="compare-tag compare-tag-light">After</span>
                    <div class="compare-divider" role="slider" tabindex="0"
                         aria-label="Drag to compare before and after"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="50"></div>
                </div>
                <p class="small mt-3 mb-0">
                    One 4 m living room on a single batten holder, then on four dimmable
                    downlights and a floor lamp. Drag the handle, or focus it and use the arrow keys.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section-eg" id="about">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-5 reveal">
                <span class="eg-eyebrow">About us</span>
                <h2 class="section-title mb-3">From hello to glow</h2>
                <p class="section-lead">
                    Eco Glow Lighting is a small Melbourne team, and five materials cover
                    everything on these pages. Keeping the list that short is the deliberate
                    part: it is what lets a wall sconce and a floor lamp bought a year apart
                    sit in one room without either looking like the odd one out.
                </p>
                <a class="btn btn-eg-ghost mt-2" href="<?= h($contactUrl) ?>">Talk to us</a>
            </div>
            <div class="col-lg-7 reveal" data-reveal-step="2">
                <?php
                /* A definition list, not a set of headings: each row really is
                   a term and its definition, and a <dl> keeps five material
                   names out of the heading outline, where they would have to
                   sit under this section's own h2.

                   The swatch sits inside the <dd>, because the wrapping <div>
                   of a <dl> may only hold terms and definitions, and it is
                   lifted out of the row by position. It carries an empty alt: a
                   macro of a surface the definition has already described in
                   words has nothing to add to a screen reader, and taking it
                   out of the flow leaves each term read immediately before its
                   own definition. */
                ?>
                <dl class="eg-materials">
                    <?php foreach ($materials as $material) : ?>
                        <div class="eg-material">
                            <dt><?= h($material['name']) ?></dt>
                            <dd>
                                <?= $this->Html->image(
                                    'materials/' . $material['image'],
                                    [
                                        'alt' => '',
                                        'class' => 'eg-material-swatch',
                                        'width' => 320,
                                        'height' => 320,
                                        'loading' => 'lazy',
                                    ],
                                ) ?>
                                <?= h($material['text']) ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
    </div>
</section>

<section class="section-eg eg-band-alt">
    <div class="container text-center reveal">
        <span class="eg-eyebrow">Ready when you are</span>
        <h2 class="section-title">Ready to transform your space?</h2>
        <p class="section-lead mx-auto mt-3">
            Send a room, a photo, or a switchboard question. Jordan will come back with a
            fitting list, the colour temperature to run it at, and an install estimate.
        </p>
        <a class="btn btn-eg-primary btn-lg mt-3" href="<?= h($contactUrl) ?>">
            Contact us
            <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </a>
    </div>
</section>
