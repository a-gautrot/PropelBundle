<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DataCollector;

use Override;
use Propel\Bundle\PropelBundle\Logger\PropelLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Caster\TraceStub;
use Symfony\Component\VarDumper\Cloner\Data;
use Throwable;

/**
 * # PropelDataCollector
 * The Propel Data Collector class collects information.
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
class PropelDataCollector extends DataCollector
{
    /**
     * @param PropelLogger $logger
     */
    public function __construct(protected PropelLogger $logger)
    {
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param Throwable|null $exception
     * @return void
     */
    #[Override]
    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'queries' => $this->cloneVar($this->buildQueries()),
            'querycount' => $this->countQueries(),
        ];
    }

    /**
     * Creates an array of Build objects.
     *
     * @return array<int, array{sql: string, connection: string, time: int|float, memory: int, trace: TraceStub}> An array of Build objects
     */
    private function buildQueries(): array
    {
        return $this->logger->getQueries();
    }

    /**
     * Returns queries.
     *
     * @return Data Queries
     */
    public function getQueries(): Data
    {
        return $this->data['queries'];
    }

    /**
     * Count queries.
     *
     * @return int The number of queries.
     */
    private function countQueries(): int
    {
        return count($this->logger->getQueries());
    }

    /**
     * Returns the collector name.
     *
     * @return string The collector name.
     */
    #[Override]
    public function getName(): string
    {
        return 'propel';
    }

    /**
     * Returns the query count.
     *
     * @return int The query count
     */
    public function getQueryCount(): int
    {
        return $this->data['querycount'];
    }

    /**
     * Returns the total time of queries.
     *
     * @return float|int The total time of queries
     */
    public function getTime(): float|int
    {
        $time = 0.0;
        foreach ($this->data['queries'] as $query) {
            $time += (float)$query['time'];
        }

        return $time;
    }

    /**
     * @return void
     */
    #[Override]
    public function reset(): void
    {
        $this->data = [];
    }
}
