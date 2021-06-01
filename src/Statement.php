<?php declare(strict_types=1);

namespace HuCron;

use InvalidArgumentException;
use function is_object;
use function is_string;

/**
 * Class Statement
 *
 * @package HuCron
 * @refer https://github.com/ajbdev/cronlingo/compare/invert-crontab
 */
class Statement
{
    /**
     * @var Cron
     */
    protected $cron;

    /**
     * @var string[][]
     */
    protected $periodMap = [
        'minute'     => ['minute', 'minutes'],
        'month'      => ['month', 'months'],
        'hour'       => ['hour', 'hours'],
        'dayOfWeek'  => ['day of the week', 'day of the week'],
        'dayOfMonth' => ['day of the month', 'day of the month'],
    ];

    /**
     * @param Cron $cron
     *
     * @return Statement
     */
    public static function fromCron(Cron $cron): self
    {
        return new self($cron);
    }

    /**
     * @param string $cronString
     *
     * @return Statement
     */
    public static function fromCronString(string $cronString): self
    {
        return new self($cronString);
    }

    /**
     * Class constructor.
     *
     * @param string|Cron $cron
     */
    public function __construct($cron)
    {
        if (is_string($cron)) {
            $this->cron = Cron::new($cron);
        } elseif (is_object($cron) && $cron instanceof Cron) {
            $this->cron = $cron;
        } else {
            throw new InvalidArgumentException('the cron only allow string or object(instance of Cron)');
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->convert();
    }

    /**
     * @return string
     */
    public function toStatement(): string
    {
        return $this->convert();
    }

    /**
     * Convert the cron expr to human statement.
     *
     * eg '30 12 * * *' to 'Every day at 12:30 AM'
     *
     * @return string
     */
    public function convert(): string
    {
        $timeParts = [
            $this->field($this->cron->hour),
            $this->field($this->cron->minute),
        ];

        // TODO ...
        return '';
    }

    public function date(Field $dayOfWeek, Field $dayOfMonth, Field $month)
    {
        $parts = [
            'dayOfMonth' => $this->field($dayOfMonth),
            'dayOfWeek'  => $this->field($dayOfWeek, FieldMap::$dayOfWeekMap),
            'month'      => $this->field($month, FieldMap::$monthMap),
        ];

        $fragment = '';

        foreach ($parts as $part => $piece) {

        }


    }

    public function time(Field $hour, Field $minute)
    {

    }

    /**
     * @param Field $field
     * @param array $map The name string to int value map. {@see Parser::$dayOfWeekMap}
     *
     * @return array
     */
    public function field(Field $field, array $map = []): array
    {
        $parts = [];
        if (count($field->getSpecific()) > 0) {
            $parts['specific'] = [];

            $map = array_flip($map);
            foreach ($field->getSpecific() as $spec) {
                $parts['specific'] = $map[$spec] ?? $spec;
            }
        }

        if ($field->getRangeMin() && $field->getRangeMax()) {
            $parts['range'] = $field->getRangeMin() . ' to ' . $field->getRangeMax();
        }

        if ($field->getRepeats()) {
            $parts['repeats'] = $field->getRepeats();
        }

        return $parts;
    }

    /**
     * @return Cron
     */
    public function getCron(): Cron
    {
        return $this->cron;
    }

    /**
     * @param Cron $cron
     */
    public function setCron(Cron $cron): void
    {
        $this->cron = $cron;
    }
}
