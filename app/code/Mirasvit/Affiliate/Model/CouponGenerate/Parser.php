<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Affiliate\Model\CouponGenerate;

use Magento\Framework\Exception\ValidatorException;

class Parser
{
    private const STATE_LENGTH_DETECTED  = 'STATE_LENGTH_DETECTED';
    private const STATE_CHARSET_DETECTED = 'STATE_CHARSET_DETECTED';

    private $pattern;

    private $parts;

    private $state = [];

    private $generatorCharset = [];

    private $generatorLength = 0;

    private $charGeneratorFactory;

    private $patternPartFactory;

    public function __construct(
        CharGeneratorFactory $charGeneratorFactory,
        PatternPartFactory   $patternPartFactory
    ) {
        $this->charGeneratorFactory = $charGeneratorFactory;
        $this->patternPartFactory   = $patternPartFactory;
    }

    public function setPattern(string $pattern): Parser
    {
        $this->pattern = $pattern;
        $this->parts   = [];

        return $this;
    }

    /**
     * @return PatternPart[]
     */
    public function getParts(): array
    {
        if (!$this->parts) {
            $this->parse();
        }

        return $this->parts;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function parse(): void
    {
        $str = $this->pattern;
        while (strlen($str) > 0) {
            $pos  = strlen($str) - 1;
            $char = substr($str, $pos, 1);
            switch ($char) {
                case '{':
                    if (!$this->isLengthDetected()) {
                        $this->throwPatternError($this->pattern, $pos);
                    }
                    break;
                case '[':
                    if (!$this->isCharsetDetected()) {
                        $this->throwPatternError($this->pattern, $pos);
                    }
                    break;
                case '}':
                    if ($this->isLengthDetected()) {
                        $this->throwPatternError($this->pattern, $pos);
                    }
                    $posLengthEnd   = $pos;
                    $posLengthStart = strrpos($str, '{');
                    if (!$posLengthStart) {
                        $this->throwPatternError($this->pattern, $pos);
                    }

                    $lengthValue           = substr($str, $posLengthStart + 1, $posLengthEnd - $posLengthStart - 1);
                    $this->generatorLength = (int)$lengthValue;
                    $this->setState(self::STATE_LENGTH_DETECTED);
                    $pos = $posLengthStart;
                    break;
                case ']':
                    if ($this->isCharsetDetected()) {
                        $this->throwPatternError($this->pattern, $pos);
                    }
                    $posCharsetEnd   = $pos;
                    $posCharsetStart = strrpos($str, '[');
                    if (!$posCharsetStart) {
                        $this->throwPatternError($this->pattern, $pos);
                    }
                    $charsetPattern         = substr($str, $posCharsetStart + 1, $posCharsetEnd - $posCharsetStart - 1);
                    $this->generatorCharset = $this->parseCharsetPattern($charsetPattern);
                    $this->setState(self::STATE_CHARSET_DETECTED);
                    $pos = $posCharsetStart;

                    $length    = $this->isLengthDetected() ? $this->generatorLength : 1;
                    $generator = $this->charGeneratorFactory->create(['charset' => $this->generatorCharset, 'length' => $length]);
                    $part      = $this->patternPartFactory->create([
                        'data' => [
                            PatternPart::TYPE      => PatternPart::TYPE_GENERATOR,
                            PatternPart::GENERATOR => $generator,
                        ],
                    ]);
                    $this->addParsedPart($part);

                    break;
                default:
                    if ($this->isLengthDetected()) {
                        $charset   = [$char];
                        $generator = $this->charGeneratorFactory->create(['charset' => $charset, 'length' => $this->generatorLength]);
                        $part      = $this->patternPartFactory->create([
                            'data' => [
                                PatternPart::TYPE      => PatternPart::TYPE_GENERATOR,
                                PatternPart::GENERATOR => $generator,
                            ],
                        ]);
                    } else {
                        $part = $this->patternPartFactory->create([
                            'data' => [
                                PatternPart::TYPE => PatternPart::TYPE_CHAR,
                                PatternPart::CHAR => $char,
                            ],
                        ]);

                    }
                    $this->addParsedPart($part);
            }

            $str = substr($str, 0, $pos);
        }
    }

    private function parseCharsetPattern(string $charsetPattern): array
    {
        if (!$charsetPattern) {
            return [];
        }

        $charset = [];
        $len     = strlen($charsetPattern);
        for ($i = 0; $i <= $len - 1; $i++) {
            $char     = $charsetPattern[$i];
            $nextChar = $charsetPattern[$i + 1] ?? '';
            if ($nextChar == '-') {
                $charBegin = $char;
                $charEnd   = $charsetPattern[$i + 2] ?? '';
                if (!$charEnd) {
                    $this->throwPatternError($charsetPattern, $i + 2);
                }

                if (!$this->isBelongingSameRange($charBegin, $charEnd)) {
                    $this->throwPatternError($charsetPattern, $i + 2);
                }

                $charset = array_merge($charset, range($charBegin, $charEnd));
                $i       += 2;
            } else {
                $charset[] = $char;
            }
        }

        return $charset;
    }

    private function isBelongingSameRange(string $charBegin, string $charEnd): bool
    {
        $rangeUpper  = range('A', 'Z');
        $rangeLower  = range('a', 'z');
        $rangeNumber = range('0', '9');
        $rangeNumber = array_map(function ($c) {
            return (string)$c;
        }, $rangeNumber);

        $isBothUpper  = in_array($charBegin, $rangeUpper, true) && in_array($charEnd, $rangeUpper, true);
        $isBothLower  = in_array($charBegin, $rangeLower, true) && in_array($charEnd, $rangeLower, true);
        $isBothNumber = in_array($charBegin, $rangeNumber, true) && in_array($charEnd, $rangeNumber, true);

        return $isBothUpper || $isBothLower || $isBothNumber;
    }

    private function addParsedPart(PatternPart $part)
    {
        array_unshift($this->parts, $part);

        $this->state            = [];
        $this->generatorCharset = [];
        $this->generatorLength  = 0;
    }

    private function setState(string $key)
    {
        $this->state[$key] = true;
    }

    private function isLengthDetected(): bool
    {
        return (bool)($this->state[self::STATE_LENGTH_DETECTED] ?? false);
    }

    private function isCharsetDetected(): bool
    {
        return (bool)($this->state[self::STATE_CHARSET_DETECTED] ?? false);
    }

    private function throwPatternError(string $pattern, int $position): void
    {
        throw new ValidatorException(
            __(
                'Wrong coupon pattern "%pattern". Possibly an error in position %position',
                [
                    'position' => $position,
                    'pattern'  => $pattern,
                ]
            )
        );
    }
}
