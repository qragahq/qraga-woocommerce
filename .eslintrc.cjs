module.exports = {
    root: true,
    env: { browser: true, es2020: true, node: true },
    extends: ['eslint:recommended', 'plugin:@typescript-eslint/recommended', 'plugin:react-hooks/recommended'],
    ignorePatterns: [
        'dist',
        'build',
        'node_modules',
        '.eslintrc.cjs',
        'includes/admin/assets',
        'includes/admin/blocks/qraga-product-widget/build'
    ],
    parser: '@typescript-eslint/parser',
    plugins: ['react-refresh'],
    rules: {
        'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
        'no-unused-vars': 'off',
        '@typescript-eslint/no-unused-vars': 'warn',
        '@typescript-eslint/no-explicit-any': 'warn',
    },
    overrides: [
        {
            files: ['includes/admin/backend/**/*.{ts,tsx}'],
            extends: ['eslint:recommended', 'plugin:@typescript-eslint/recommended', 'plugin:react-hooks/recommended'],
        },
        {
            files: ['includes/admin/blocks/**/*.js'],
            extends: ['plugin:@wordpress/eslint-plugin/recommended'],
            env: {
                browser: true,
                es6: true,
            },
        },
        {
            files: ['**/*.cjs'],
            env: {
                node: true,
            },
            rules: {
                '@typescript-eslint/no-var-requires': 'off',
                'import/no-commonjs': 'off',
            },
        }
    ]
}; 