const purgecss = require('@fullhuman/postcss-purgecss');

module.exports = ({ env }) => ({
    plugins: [
        require('autoprefixer'),
        purgecss({
            content: [
                './templates/**/*.html.twig',
                './src/**/*.php',
                './assets/js/**/*.js'
            ],
            defaultExtractor: content => content.match(/[\w-/:%.]+(?<!:)/g) || [],
            safelist: {
                standard: [
					/^fa-/, /^fas$/, /^far$/, /^fab$/, /^fa$/,
					/^modal/, /^collapse/, /^fade/, /^show/, /^active/,
					/^dropdown/, /^tooltip/, /^popover/, /^carousel/, /^image/,
					/^btn-/, /^alert-/, /^badge-/,
                ],
                deep: [/^data-bs-/],
            },
        }),
    ].filter(Boolean),
});