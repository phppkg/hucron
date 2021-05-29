<?php declare(strict_types=1);

namespace HuCron;

use InvalidArgumentException;
use function is_object;
use function is_string;

/**
 * Class Statement
 *
 * @package HuCron
 */
class Statement
{
    /**
     * @var Cron
     */
    protected $cron;

    /**
     * @var string
     */
    protected $statement;

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
        return $this->generate();
    }

    /**
     * Generate the human statement string.
     *
     * eg '30 12 * * *' to 'Every day at 12:30 AM'
     *
     * @return string
     */
    public function generate(): string
    {
        // TODO ...
        return '';
    }

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
