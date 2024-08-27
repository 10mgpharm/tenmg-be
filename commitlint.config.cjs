module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'header-max-length': [2, 'always', 72],
        'subject-case': [2, 'always', ['lower-case', 'camel-case', 'sentence-case']],
        'type-enum': [2, 'always', [
            'build',
            'chore',
            'ci',
            'docs',
            'feat',
            'fix',
            'perf',
            'refactor',
            'style',
            'test'
        ]],
        // Add or override more rules as needed
    },
};
