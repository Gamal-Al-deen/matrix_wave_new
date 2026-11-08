import assert from 'node:assert/strict';
import test from 'node:test';

import {
    ACCEPTED_INPUT_MIME_TYPES,
    MAX_IMAGE_DIMENSION,
    ProductImageOptimizationError,
    WEBP_MIME_TYPE,
    WEBP_QUALITY,
    calculateTargetDimensions,
    isAcceptedProductImage,
    productImageFileName,
} from '../../resources/js/product-image-optimizer.js';

test('the optimization policy constants match the specification', () => {
    assert.equal(MAX_IMAGE_DIMENSION, 1200);
    assert.equal(WEBP_QUALITY, 0.8);
    assert.equal(WEBP_MIME_TYPE, 'image/webp');
    assert.deepEqual(ACCEPTED_INPUT_MIME_TYPES, ['image/jpeg', 'image/png', 'image/webp']);
});

test('a large landscape image is limited to 1200 px on its longest edge', () => {
    assert.deepEqual(calculateTargetDimensions(2400, 1200), { width: 1200, height: 600 });
});

test('a large portrait image is limited to 1200 px on its longest edge', () => {
    assert.deepEqual(calculateTargetDimensions(1200, 2400), { width: 600, height: 1200 });
});

test('a large square image becomes 1200 x 1200', () => {
    assert.deepEqual(calculateTargetDimensions(3000, 3000), { width: 1200, height: 1200 });
});

test('aspect ratio is preserved within integer rounding', () => {
    assert.deepEqual(calculateTargetDimensions(4000, 3000), { width: 1200, height: 900 });
    assert.deepEqual(calculateTargetDimensions(3000, 4000), { width: 900, height: 1200 });
    assert.deepEqual(calculateTargetDimensions(3333, 1111), { width: 1200, height: 400 });
});

test('images already within the limit keep their dimensions', () => {
    assert.deepEqual(calculateTargetDimensions(1200, 800), { width: 1200, height: 800 });
    assert.deepEqual(calculateTargetDimensions(800, 1200), { width: 800, height: 1200 });
    assert.deepEqual(calculateTargetDimensions(640, 480), { width: 640, height: 480 });
});

test('small images are never upscaled', () => {
    assert.deepEqual(calculateTargetDimensions(300, 200), { width: 300, height: 200 });
    assert.deepEqual(calculateTargetDimensions(1, 1), { width: 1, height: 1 });
});

test('invalid source dimensions are rejected', () => {
    assert.throws(() => calculateTargetDimensions(0, 100), ProductImageOptimizationError);
    assert.throws(() => calculateTargetDimensions(Number.NaN, 100), ProductImageOptimizationError);
});

test('the output file name always ends with .webp', () => {
    assert.equal(productImageFileName('photo.JPG'), 'photo.webp');
    assert.equal(productImageFileName('photo.jpeg'), 'photo.webp');
    assert.equal(productImageFileName('folder/photo.png'), 'photo.webp');
    assert.equal(productImageFileName('a.b.webp'), 'a.b.webp');
    assert.equal(productImageFileName('no-extension'), 'no-extension.webp');
    assert.equal(productImageFileName(''), 'product-image.webp');
});

test('only the existing raster image types are accepted', () => {
    assert.equal(isAcceptedProductImage({ type: 'image/jpeg', name: 'photo.jpg' }), true);
    assert.equal(isAcceptedProductImage({ type: 'image/png', name: 'photo.png' }), true);
    assert.equal(isAcceptedProductImage({ type: 'image/webp', name: 'photo.webp' }), true);
    assert.equal(isAcceptedProductImage({ type: 'image/heic', name: 'photo.heic' }), false);
    assert.equal(isAcceptedProductImage({ type: 'image/gif', name: 'photo.gif' }), false);
    assert.equal(isAcceptedProductImage({ type: '', name: 'photo.JPG' }), true);
    assert.equal(isAcceptedProductImage({ type: '', name: 'photo.gif' }), false);
    assert.equal(isAcceptedProductImage(null), false);
});
