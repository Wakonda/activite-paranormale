const purgecss = require('@fullhuman/postcss-purgecss');

module.exports = ({ env }) => ({
    plugins: [
        require('autoprefixer'),
        purgecss({
            content: [
                './templates/**/*.html.twig',
                './assets/js/**/*.js',
                // ajoute tout fichier qui génère des classes dynamiquement
            ],
            defaultExtractor: content => content.match(/[\w-/:%.]+(?<!:)/g) || [],
            safelist: {
                standard: [
					/^fa-/, /^fas$/, /^far$/, /^fab$/, /^fa$/,
					/^modal/, /^collapse/, /^fade/, /^show/, /^active/,
					/^dropdown/, /^tooltip/, /^popover/, /^carousel/,
					/^btn-/, /^alert-/, /^badge-/,
                ],
                deep: [/^data-bs-/],
            },
        }),
    ].filter(Boolean),
});