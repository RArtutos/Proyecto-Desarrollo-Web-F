<?php
declare(strict_types=1);

$hashBD = '$argon2id$v=19$m=65536,t=4,p=1$ZERzSXpGakdFVFNPeExxMg$gj2GwiIVr8ziCEEwmU4I81FuQdr1XC3YPEsUIfIHxmo';

$pass = 'uv123456789';

echo "HASH LEN: ", strlen($hashBD), PHP_EOL;
echo "HASH INFO: ", json_encode(password_get_info($hashBD), JSON_UNESCAPED_SLASHES), PHP_EOL;

$ok = password_verify($pass, $hashBD);
echo "VERIFY: ", ($ok ? "TRUE" : "FALSE"), PHP_EOL;

echo "HASH HEX (tail): ", substr(bin2hex($hashBD), -80), PHP_EOL;
