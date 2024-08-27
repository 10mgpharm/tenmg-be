import globals from "globals";
import pluginJs from "@eslint/js";


export default [
    {
        languageOptions: { globals: globals.browser },
        parser: '@typescript-eslint/parser',
        extends: [
            'eslint:recommended',
            'plugin:@typescript-eslint/recommended',
            'airbnb-base',
        ],
        parserOptions: {
            ecmaVersion: 2020,
            sourceType: 'module',
        },
        rules: {
            // Your custom rules
        },
    },
  pluginJs.configs.recommended,
];
