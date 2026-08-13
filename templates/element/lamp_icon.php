<?php
/**
 * One line-art lamp mark, chosen by key.
 *
 * The catalogue is not modelled yet, so the placeholder product arrays in the
 * storefront templates carry an icon *key* ('floor', 'ceiling', …) rather than
 * a blob of SVG markup. That is also the shape a real `products` row would
 * take — a short identifier in a column, resolved to a drawing at render time —
 * so when the table lands nothing about these templates has to change.
 *
 * @var \App\View\AppView $this
 * @var string $name Icon key. An unknown key falls back to the ceiling mark.
 * @var string|null $class Extra classes for the <svg> element.
 */
$marks = [
    'ceiling' => '<path d="M12 2v4"/><path d="M4 13a8 8 0 0 1 16 0z"/><path d="M9.5 13a2.5 2.5 0 0 0 5 0"/>',
    'floor' => '<path d="M8 3h8l2 7H6z"/><path d="M12 10v9"/><path d="M8.5 21h7"/>',
    'smart' => '<path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.8.6 1.5 1.6 1.5 2.6V17h4v-.5c0-1 .7-2 1.5-2.6A6 6 0 0 0 12 3z"/><path d="M12 9v3"/>',
    'solar' => '<circle cx="12" cy="8" r="3"/><path d="M12 2v1.5"/><path d="M6.7 5.7l1 1"/><path d="M17.3 5.7l-1 1"/><path d="M12 13v3"/><path d="M6 21l2-5h8l2 5"/>',
    'decor' => '<path d="M12 3l1.8 4.6L18.5 9l-4.7 1.4L12 15l-1.8-4.6L5.5 9l4.7-1.4L12 3z"/><path d="M18.5 15l.8 1.9 1.9.8-1.9.8-.8 1.9-.8-1.9-1.9-.8 1.9-.8z"/>',
    'wall' => '<path d="M4 4v16"/><path d="M8 9h6l3 5H8z"/><path d="M8 9V7a1 1 0 0 1 1-1h2"/>',
];

$mark = $marks[$name] ?? $marks['ceiling'];
$svgClass = ($class ?? '') !== '' ? ' class="' . h($class) . '"' : '';
?>
<svg<?= $svgClass ?> viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $mark ?></svg>
