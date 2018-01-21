<html>

<head>
    <title>
        Oauth Verifier
    </title>

    <style>
        .verifier {
            color: black;
            background-color: white;
            border: 2px gray solid;
            text-align: center;
            width: 20%;
            margin-top: 20%;
            margin-left: 40%;
        }

        .verifier p {
            font-family: Arial, Helvetica, Sans-Serif;
            font-size: 10pt;
        }


        .verifier pre {
            font-family: courier new, monospace;
            font-size: 24pt;
            font-weight: bold;
        }

        body {
            background-color: #1873F3;
        }
    </style>
</head>

<body>

<div class='verifier'>
    <p>Oauth verification code is:</p>

    <pre><?php if (verifier_looks_ok($_GET['oauth_verifier'])) echo $_GET['oauth_verifier']; ?></pre>
</div>

</body>

</html>

<?php

    /**
     * Do some basic checcking of the oauth_verifier string
     *
     * @param string $verifier the verifier string to check
     *
     * @return boolean true if verifier looks ok to output, false otherwise
     */
    function verifier_looks_ok ($verifier) {
        // only a-zA-Z0-9 are allowed
        if (preg_match('/[^a-zA-Z0-9]/', $verifier) !== 0) {
            return false;
        }

        if (strlen($verifier) > 60) {
            return false;
        }

        return true;
    }

?>
