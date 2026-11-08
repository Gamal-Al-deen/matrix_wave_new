/**
 * Client-side product image optimization.
 *
 * Product images are decoded in the browser, scaled down so the longest edge
 * is at most 1200 px without cropping or upscaling, and encoded as WebP at
 * 80% quality before they are sent to Laravel / Supabase Storage.
 */

export const MAX_IMAGE_DIMENSION = 1200;
export const WEBP_QUALITY = 0.8;
export const WEBP_MIME_TYPE = 'image/webp';

export const ACCEPTED_INPUT_MIME_TYPES = Object.freeze([
    'image/jpeg',
    'image/png',
    'image/webp',
]);

const ACCEPTED_INPUT_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'webp']);

const messages = {
    unsupported: 'صيغة الصورة غير مدعومة. اختر صورة JPG أو PNG أو WebP.',
    empty: 'الملف المحدد فارغ. اختر صورة صالحة.',
    decode: 'تعذر قراءة الصورة. تأكد من أن الملف صورة صالحة وحاول مرة أخرى.',
    canvas: 'تعذر تجهيز الصورة للمعالجة. حاول مرة أخرى.',
    encode: 'تعذر تحويل الصورة إلى WebP. حاول مرة أخرى.',
    file: 'تعذر إنشاء ملف WebP. حاول مرة أخرى.',
};

export class ProductImageOptimizationError extends Error {
    constructor(message, cause = null) {
        super(message);
        this.name = 'ProductImageOptimizationError';
        this.cause = cause;
    }
}

/**
 * Return true when the selected file is one of the raster formats already
 * accepted by the product image forms.
 *
 * Browsers occasionally report an empty MIME type, so the extension is used
 * only as a fallback. Laravel still validates the actual file content.
 */
export const isAcceptedProductImage = (file) => {
    if (! file || typeof file !== 'object') {
        return false;
    }

    const type = typeof file.type === 'string' ? file.type.toLowerCase() : '';

    if (ACCEPTED_INPUT_MIME_TYPES.includes(type)) {
        return true;
    }

    if (type !== '') {
        return false;
    }

    const name = typeof file.name === 'string' ? file.name : '';
    const extension = name.includes('.') ? name.split('.').pop().toLowerCase() : '';

    return ACCEPTED_INPUT_EXTENSIONS.has(extension);
};

/**
 * Calculate the target size using the 1200 px longest-edge rule.
 *
 * Never upscales. Both dimensions and aspect ratio are preserved within
 * normal integer rounding.
 */
export const calculateTargetDimensions = (
    width,
    height,
    maxDimension = MAX_IMAGE_DIMENSION,
) => {
    const sourceWidth = Number(width);
    const sourceHeight = Number(height);
    const limit = Math.max(1, Math.floor(Number(maxDimension) || MAX_IMAGE_DIMENSION));

    if (
        ! Number.isFinite(sourceWidth)
        || ! Number.isFinite(sourceHeight)
        || sourceWidth < 1
        || sourceHeight < 1
    ) {
        throw new ProductImageOptimizationError(messages.decode);
    }

    const roundedWidth = Math.max(1, Math.round(sourceWidth));
    const roundedHeight = Math.max(1, Math.round(sourceHeight));
    const longestEdge = Math.max(roundedWidth, roundedHeight);

    if (longestEdge <= limit) {
        return { width: roundedWidth, height: roundedHeight };
    }

    const scale = limit / longestEdge;

    return {
        width: Math.max(1, Math.round(roundedWidth * scale)),
        height: Math.max(1, Math.round(roundedHeight * scale)),
    };
};

/**
 * Build the output file name: the original base name with a .webp suffix.
 */
export const productImageFileName = (originalName) => {
    const rawName = typeof originalName === 'string' ? originalName : '';
    const fileName = rawName.split(/[\\/]/).pop() || '';
    const baseName = fileName.replace(/\.[^.]+$/, '').trim();

    return `${baseName || 'product-image'}.webp`;
};

const decodeWithImageBitmap = async (file) => {
    if (typeof createImageBitmap !== 'function') {
        return null;
    }

    const bitmap = await new Promise((resolve, reject) => {
        let settled = false;
        const timer = setTimeout(() => {
            if (settled) {
                return;
            }

            settled = true;
            reject(new Error('ImageBitmap decoding timed out.'));
        }, 8000);

        createImageBitmap(file).then(
            (result) => {
                if (settled) {
                    result.close?.();

                    return;
                }

                settled = true;
                clearTimeout(timer);
                resolve(result);
            },
            (error) => {
                if (settled) {
                    return;
                }

                settled = true;
                clearTimeout(timer);
                reject(error);
            },
        );
    });

    return {
        source: bitmap,
        width: bitmap.width,
        height: bitmap.height,
        close: () => bitmap.close?.(),
    };
};

const decodeWithImageElement = (file) => new Promise((resolve, reject) => {
    if (typeof URL.createObjectURL !== 'function') {
        reject(new ProductImageOptimizationError(messages.decode));
        return;
    }

    const objectUrl = URL.createObjectURL(file);
    const image = new Image();
    let settled = false;

    const cleanup = () => {
        URL.revokeObjectURL(objectUrl);
        image.onload = null;
        image.onerror = null;
    };

    image.onload = () => {
        if (settled) {
            return;
        }

        settled = true;

        resolve({
            source: image,
            width: image.naturalWidth,
            height: image.naturalHeight,
            close: () => {
                image.src = '';
                cleanup();
            },
        });
    };

    image.onerror = () => {
        if (settled) {
            return;
        }

        settled = true;
        cleanup();
        reject(new ProductImageOptimizationError(messages.decode));
    };

    image.src = objectUrl;
});

const decodeImage = async (file) => {
    try {
        const decoded = await decodeWithImageBitmap(file);

        if (decoded) {
            return decoded;
        }
    } catch (error) {
        // Fall back to an HTMLImageElement; some browsers reject particular
        // color profiles or formats that the element can still decode.
    }

    try {
        return await decodeWithImageElement(file);
    } catch (error) {
        if (error instanceof ProductImageOptimizationError) {
            throw error;
        }

        throw new ProductImageOptimizationError(messages.decode, error);
    }
};

const ENCODE_TIMEOUT_MS = 15000;

const encodeCanvasAsWebp = (canvas) => new Promise((resolve, reject) => {
    if (typeof canvas.toBlob !== 'function') {
        reject(new ProductImageOptimizationError(messages.encode));
        return;
    }

    let settled = false;
    const timer = setTimeout(() => {
        if (settled) {
            return;
        }

        settled = true;
        reject(new ProductImageOptimizationError(messages.encode));
    }, ENCODE_TIMEOUT_MS);

    const settle = (callback, value) => {
        if (settled) {
            return;
        }

        settled = true;
        clearTimeout(timer);
        callback(value);
    };

    try {
        canvas.toBlob((blob) => {
            if (! blob || blob.size < 1) {
                settle(reject, new ProductImageOptimizationError(messages.encode));

                return;
            }

            if (blob.type !== WEBP_MIME_TYPE) {
                settle(reject, new ProductImageOptimizationError(messages.encode));

                return;
            }

            settle(resolve, blob);
        }, WEBP_MIME_TYPE, WEBP_QUALITY);
    } catch (error) {
        settle(reject, new ProductImageOptimizationError(messages.encode, error));
    }
});

/**
 * Optimize one selected product image.
 *
 * Returns a new File named *.webp with an exact image/webp MIME type. Throws
 * ProductImageOptimizationError instead of ever returning the original file.
 */
export const optimizeProductImage = async (file) => {
    if (! isAcceptedProductImage(file)) {
        throw new ProductImageOptimizationError(messages.unsupported);
    }

    if (typeof file.size === 'number' && file.size < 1) {
        throw new ProductImageOptimizationError(messages.empty);
    }

    let decoded = null;
    let canvas = null;

    try {
        decoded = await decodeImage(file);

        const target = calculateTargetDimensions(decoded.width, decoded.height);

        if (typeof document === 'undefined') {
            throw new ProductImageOptimizationError(messages.canvas);
        }

        canvas = document.createElement('canvas');
        canvas.width = target.width;
        canvas.height = target.height;

        const context = canvas.getContext('2d');

        if (! context) {
            throw new ProductImageOptimizationError(messages.canvas);
        }

        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';
        context.drawImage(decoded.source, 0, 0, target.width, target.height);

        const blob = await encodeCanvasAsWebp(canvas);
        const output = new File([blob], productImageFileName(file.name), {
            type: WEBP_MIME_TYPE,
            lastModified: Date.now(),
        });

        if (output.type !== WEBP_MIME_TYPE || output.size < 1) {
            throw new ProductImageOptimizationError(messages.file);
        }

        return output;
    } catch (error) {
        if (error instanceof ProductImageOptimizationError) {
            throw error;
        }

        throw new ProductImageOptimizationError(messages.encode, error);
    } finally {
        try {
            decoded?.close?.();
        } catch (error) {
            // Resource cleanup must not mask the original result.
        }

        if (canvas) {
            canvas.width = 0;
            canvas.height = 0;
        }
    }
};
