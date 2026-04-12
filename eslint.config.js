import globals from "globals";

export default [
    {
        ignores: ["vendor/**"],
    },
    {
        files: ["script.js", "script/**/*.js"],
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: "script",
            globals: {
                ...globals.browser,
                ...globals.jquery,
                BpmnJS: "readonly",
                DmnJS: "readonly",
                DmnJSViewer: "readonly",
            },
        },
        rules: {
            "no-unused-vars": "warn",
            "no-undef": "error",
            "eqeqeq": "warn",
            "no-console": ["warn", { allow: ["warn", "error"] }],
            "curly": ["warn", "multi-line"],
            "no-eval": "error",
            "no-implied-eval": "error",
        },
    },
];
