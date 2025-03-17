<?php
class AESBase62Utility {
    private const STATIC_KEY = "CHARMI#CANDECIDE"; // 16-byte key
    private const BASE62_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    // Encrypt the given text and return Base62 encoded string
    public static function encrypt($data) {
        $encrypted = openssl_encrypt(
            $data,
            'AES-128-ECB',
            self::STATIC_KEY,
            OPENSSL_RAW_DATA
        );

        return self::base62_encode($encrypted);
    }

    // Decrypt the given Base62 encoded string and return the original text
    public static function decrypt($encryptedData) {
        $decodedBytes = self::base62_decode($encryptedData);

        return openssl_decrypt(
            $decodedBytes,
            'AES-128-ECB',
            self::STATIC_KEY,
            OPENSSL_RAW_DATA
        );
    }

    // Convert bytes to Base62 encoded string
    private static function base62_encode($bytes) {
        $number = "0";

        // Convert binary data to a big integer
        for ($i = 0, $len = strlen($bytes); $i < $len; $i++) {
            $number = bcmul($number, "256");
            $number = bcadd($number, (string)ord($bytes[$i]));
        }

        // Convert big integer to Base62
        $result = "";
        while (bccomp($number, "0") > 0) {
            $remainder = bcmod($number, "62");
            $number = bcdiv($number, "62", 0);
            $result = self::BASE62_CHARS[(int)$remainder] . $result;
        }

        // Ensure a minimum length of 16 characters (padding with 'A' if necessary)
        while (strlen($result) < 16) {
            $result = 'A' . $result;
        }

        return $result;
    }

    // Convert Base62 encoded string back to bytes
    private static function base62_decode($base62) {
        $number = "0";

        for ($i = 0, $len = strlen($base62); $i < $len; $i++) {
            $number = bcmul($number, "62");
            $number = bcadd($number, (string)strpos(self::BASE62_CHARS, $base62[$i]));
        }

        return self::bcdecbinary($number);
    }

    // Convert big integer to binary bytes
    private static function bcdecbinary($dec) {
        $bin = "";
        while ($dec > 0) {
            $bin = chr(bcmod($dec, 256)) . $bin;
            $dec = bcdiv($dec, 256, 0);
        }
        return $bin;
    }
}



// $decryptedText = AESBase62Utility::decrypt("F9ZVYH6vEkvSQuNSFFjpwK");

// echo "<p>Decrypted: $decryptedText</p>";

?>
