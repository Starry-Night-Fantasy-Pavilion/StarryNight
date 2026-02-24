<?php
namespace gateways\global_wx_pay\lib;

class AesUtil
{
    /**
     * AES key
     *
     * @var string
     */
    private $aesKey;
    const KEY_LENGTH_BYTE = 32;
    const AUTH_TAG_LENGTH_BYTE = 16;
    public function __construct($aesKey)
    {
        if (strlen($aesKey) != self::KEY_LENGTH_BYTE) {
            throw new InvalidArgumentException("无效的ApiV3Key，长度应为32个字节");
        }
        $this->aesKey = $aesKey;
    }
    public function decryptToString($associatedData, $nonceStr, $ciphertext)
    {
        $ciphertext = base64_decode($ciphertext);
        if (strlen($ciphertext) <= self::AUTH_TAG_LENGTH_BYTE) {
            return false;
        }
        if (function_exists("\\sodium_crypto_aead_aes256gcm_is_available") && sodium_crypto_aead_aes256gcm_is_available()) {
            return sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->aesKey);
        }
        if (function_exists("\\Sodium\\crypto_aead_aes256gcm_is_available") && crypto_aead_aes256gcm_is_available()) {
            return crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->aesKey);
        }
        if (70100 <= PHP_VERSION_ID && in_array("aes-256-gcm", openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -1 * self::AUTH_TAG_LENGTH_BYTE);
            $authTag = substr($ciphertext, -1 * self::AUTH_TAG_LENGTH_BYTE);
            return openssl_decrypt($ctext, "aes-256-gcm", $this->aesKey, OPENSSL_RAW_DATA, $nonceStr, $authTag, $associatedData);
        }
        throw new \RuntimeException("AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php");
    }
}

?>