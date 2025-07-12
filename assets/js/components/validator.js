
/* ==================================
   Frontend Validator Component
   ================================== */

export function validateInput(input, type = 'text', maxLength = 100) {
    if (!input || typeof input !== 'string') {
        return false;
    }

    if (input.length > maxLength) {
        return false;
    }

    const xssPatterns = [
        /<script[^>]*>.*?<\/script>/gi,
        /javascript:/gi,
        /on\w+\s*=/gi,
        /<iframe[^>]*>/gi,
        /<object[^>]*>/gi,
        /<embed[^>]*>/gi
    ];

    for (const pattern of xssPatterns) {
        if (pattern.test(input)) {
            return false;
        }
    }

    const sqlPatterns = [
        /(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b|\btruncate\b)/gi,
        /(\--|\#|\/\*|\*\/)/gi,
        /(\bor\b\s+\d+\s*=\s*\d+|\band\b\s+\d+\s*=\s*\d+)/gi
    ];

    for (const pattern of sqlPatterns) {
        if (pattern.test(input)) {
            return false;
        }
    }

    return true;
}

export function sanitizeInput(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}
