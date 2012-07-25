/* ------------------------------------------------------------------------
 * Markup toolbar
 * ------------------------------------------------------------------------ */
STUDIP.Markup = {
    buttonSet: [
        {"name": "bold",          "label": "<strong>B</strong>", open: "**",         close: "**"},
        {"name": "italic",        "label": "<em>i</em>",         open: "%%",         close: "%%"},
        {"name": "underline",     "label": "<u>u</u>",           open: "__",         close: "__"},
        {"name": "strikethrough", "label": "<del>u</del>",       open: "{-",         close: "-}"},
        {"name": "code",          "label": "code",               open: "[code]",     close: "[/code]"},
        {"name": "larger",        "label": "A+",                 open: "++",         close: "++"},
        {"name": "smaller",       "label": "A-",                 open: "--",         close: "--"},
        {"name": "signature",     "label": "signature",          open: "\u2013~~~",  close: ""}
    ]
};
