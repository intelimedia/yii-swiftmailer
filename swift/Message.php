<?php

namespace bashkarev\swiftmailer\swift;

use bashkarev\swiftmailer\BaseMessage;

/**
 * Message implements a message class based on SwiftMailer.
 * @method Mailer getMailer() returns mailer instance.
 * @property \Swift_Message $swiftMessage Swift message instance. This property is read-only.
 *
 * @author Dmitriy Bashkarev <dmitriy@bashkarev.com>
 * @author Paul Klimov <klimov.paul@gmail.com>
 */
class Message extends BaseMessage
{
    /**
     * @var \Swift_Message Swift message instance.
     */
    private $_swiftMessage;

    /**
     * @var \Swift_Signer[] attached signers
     */
    private $signers = [];

    /**
     * @return \Swift_Message Swift message instance.
     */
    public function getSwiftMessage()
    {
        if (!is_object($this->_swiftMessage)) {
            $this->_swiftMessage = $this->createSwiftMessage();
        }

        return $this->_swiftMessage;
    }

    /**
     * @inheritdoc
     */
    public function getCharset()
    {
        return $this->getSwiftMessage()->getCharset();
    }

    /**
     * @inheritdoc
     */
    public function setCharset($charset)
    {
        $this->getSwiftMessage()->setCharset($charset);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFrom()
    {
        return $this->getSwiftMessage()->getFrom();
    }

    /**
     * @inheritdoc
     */
    public function setFrom($from)
    {
        $this->getSwiftMessage()->setFrom($from);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getReplyTo()
    {
        return $this->getSwiftMessage()->getReplyTo();
    }

    /**
     * @inheritdoc
     */
    public function setReplyTo($replyTo)
    {
        $this->getSwiftMessage()->setReplyTo($replyTo);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTo()
    {
        return $this->getSwiftMessage()->getTo();
    }

    /**
     * @inheritdoc
     */
    public function setTo($to)
    {
        $this->getSwiftMessage()->setTo($to);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCc()
    {
        return $this->getSwiftMessage()->getCc();
    }

    /**
     * @inheritdoc
     */
    public function setCc($cc)
    {
        $this->getSwiftMessage()->setCc($cc);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBcc()
    {
        return $this->getSwiftMessage()->getBcc();
    }

    /**
     * @inheritdoc
     */
    public function setBcc($bcc)
    {
        $this->getSwiftMessage()->setBcc($bcc);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubject()
    {
        return $this->getSwiftMessage()->getSubject();
    }

    /**
     * @inheritdoc
     */
    public function setSubject($subject)
    {
        $this->getSwiftMessage()->setSubject($subject);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setTextBody($text)
    {
        $this->setBody($text, 'text/plain');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setHtmlBody($html)
    {
        $this->setBody($html, 'text/html');

        return $this;
    }

    /**
     * Sets the message body.
     * If body is already set and its content type matches given one, it will
     * be overridden, if content type miss match the multipart message will be composed.
     * @param string $body body content.
     * @param string $contentType body content type.
     */
    protected function setBody($body, $contentType)
    {
        $message = $this->getSwiftMessage();
        $oldBody = $message->getBody();
        $charset = $message->getCharset();
        if (empty($oldBody)) {
            $parts = $message->getChildren();
            $partFound = false;
            foreach ($parts as $key => $part) {
                if (!($part instanceof \Swift_Mime_Attachment)) {
                    /* @var $part \Swift_Mime_MimePart */
                    if ($part->getContentType() == $contentType) {
                        $charset = $part->getCharset();
                        unset($parts[$key]);
                        $partFound = true;
                        break;
                    }
                }
            }
            if ($partFound) {
                reset($parts);
                $message->setChildren($parts);
                $message->addPart($body, $contentType, $charset);
            } else {
                $message->setBody($body, $contentType);
            }
        } else {
            $oldContentType = $message->getContentType();
            if ($oldContentType == $contentType) {
                $message->setBody($body, $contentType);
            } else {
                $message->setBody(null);
                $message->setContentType(null);
                $message->addPart($oldBody, $oldContentType, $charset);
                $message->addPart($body, $contentType, $charset);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function attach($fileName, array $options = [])
    {
        $attachment = \Swift_Attachment::fromPath($fileName);
        if (!empty($options['fileName'])) {
            $attachment->setFilename($options['fileName']);
        }
        if (!empty($options['contentType'])) {
            $attachment->setContentType($options['contentType']);
        }
        $this->getSwiftMessage()->attach($attachment);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function attachContent($content, array $options = [])
    {
        $attachment = new \Swift_Attachment($content);
        if (!empty($options['fileName'])) {
            $attachment->setFilename($options['fileName']);
        }
        if (!empty($options['contentType'])) {
            $attachment->setContentType($options['contentType']);
        }
        $this->getSwiftMessage()->attach($attachment);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function embed($fileName, array $options = [])
    {
        $embedFile = \Swift_EmbeddedFile::fromPath($fileName);
        if (!empty($options['fileName'])) {
            $embedFile->setFilename($options['fileName']);
        }
        if (!empty($options['contentType'])) {
            $embedFile->setContentType($options['contentType']);
        }

        return $this->getSwiftMessage()->embed($embedFile);
    }

    /**
     * @inheritdoc
     */
    public function embedContent($content, array $options = [])
    {
        $embedFile = new \Swift_EmbeddedFile($content);
        if (!empty($options['fileName'])) {
            $embedFile->setFilename($options['fileName']);
        }
        if (!empty($options['contentType'])) {
            $embedFile->setContentType($options['contentType']);
        }

        return $this->getSwiftMessage()->embed($embedFile);
    }

    /**
     * Sets message signature
     * @param array|callable|\Swift_Signer $signature signature specification.
     * See [[addSignature()]] for details on how it should be specified.
     * @return $this self reference.
     * @since 2.0.6
     */
    public function setSignature($signature)
    {
        if (!empty($this->signers)) {
            // clear previously set signers
            $swiftMessage = $this->getSwiftMessage();
            foreach ($this->signers as $signer) {
                $swiftMessage->detachSigner($signer);
            }
            $this->signers = [];
        }
        return $this->addSignature($signature);
    }

    /**
     * Adds message signature.
     * @param array|callable|\Swift_Signer $signature signature specification, this can be:
     *
     * - [[\Swift_Signer]] instance
     * - callable, which returns [[\Swift_Signer]] instance
     * - configuration array for the signer creation
     *
     * @return $this self reference
     * @throws \Exception on invalid signature configuration
     * @since 2.0.6
     */
    public function addSignature($signature)
    {
        if ($signature instanceof \Swift_Signer) {
            $signer = $signature;
        } elseif (is_callable($signature)) {
            $signer = call_user_func($signature);
        } elseif (is_array($signature)) {
            $signer = $this->createSwiftSigner($signature);
        } else {
            throw new \Exception('Signature should be instance of "Swift_Signer", callable or array configuration');
        }
        $this->getSwiftMessage()->attachSigner($signer);
        $this->signers[] = $signer;
        return $this;
    }

    /**
     * Creates signer from its configuration
     * @param array $signature signature configuration
     * @return \Swift_Signer signer instance
     * @throws \Exception on invalid configuration provided
     * @since 2.0.6
     */
    protected function createSwiftSigner($signature)
    {
        if (!isset($signature['type'])) {
            throw new \Exception('Signature configuration should contain "type" key');
        }
        switch (strtolower($signature['type'])) {
            case 'dkim' :
                $domain = isset($signature['domain']) ? $signature['domain'] : null;
                $selector = isset($signature['selector']) ? $signature['selector'] : null;
                if (isset($signature['key'])) {
                    $privateKey = $signature['key'];
                } elseif (isset($signature['file'])) {
                    $privateKey = file_get_contents(\Yii::getPathOfAlias($signature['file']));
                } else {
                    throw new \Exception("Either 'key' or 'file' signature option should be specified");
                }
                return new \Swift_Signers_DKIMSigner($privateKey, $domain, $selector);
            case 'opendkim' :
                $domain = isset($signature['domain']) ? $signature['domain'] : null;
                $selector = isset($signature['selector']) ? $signature['selector'] : null;
                if (isset($signature['key'])) {
                    $privateKey = $signature['key'];
                } elseif (isset($signature['file'])) {
                    $privateKey = file_get_contents(\Yii::getPathOfAlias($signature['file']));
                } else {
                    throw new \Exception("Either 'key' or 'file' signature option should be specified");
                }
                return new \Swift_Signers_OpenDKIMSigner($privateKey, $domain, $selector);
            default:
                throw new \Exception("Unrecognized signature type '{$signature['type']}'");
        }
    }

    /**
     * @inheritdoc
     */
    public function toString()
    {
        return $this->getSwiftMessage()->toString();
    }

    /**
     * Creates the Swift email message instance.
     * @return \Swift_Message email message instance.
     */
    protected function createSwiftMessage()
    {
        return new \Swift_Message();
    }
}
