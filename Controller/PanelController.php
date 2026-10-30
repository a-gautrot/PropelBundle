<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Controller;

use Exception;
use InvalidArgumentException;
use PDO;
use Propel\Bundle\PropelBundle\DataCollector\PropelDataCollector;
use Propel\Runtime\Propel;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * # PanelController
 * PanelController is designed to display information in the Propel Panel.
 *
 * @author William DURAND <william.durand1@gmail.com>
 * @api
 */
class PanelController extends AbstractController
{
    /**
     * This method renders the global Propel configuration.
     * @return Response
     */
    public function configurationAction(): Response
    {
        return $this->render(
            '@Propel/Panel/configuration.html.twig',
            [
                'propel_version' => Propel::VERSION,
                'configuration' => $this->getParameter('propel.configuration'),
                'logging' => $this->getParameter('propel.logging'),
            ]
        );
    }

    /**
     * Renders the profiler panel for the given token.
     *
     * @param string $token The profiler token
     * @param string $connection The connection name
     * @param int $query
     *
     * @param Profiler|null $profiler
     * @return Response A Response instance
     * @throws InvalidArgumentException
     */
    public function explainAction(string $token, string $connection, int $query, ?Profiler $profiler): Response
    {
        if ($profiler === null) {
            return new Response('The profiler is unavailable.');
        }
        $profiler->disable();

        $profile = $profiler->loadProfile($token);
        if ($profile === null) {
            return new Response('This profile does not exist.');
        }

        /** @var PropelDataCollector $propelDataCollector */
        $propelDataCollector = $profile->getCollector('propel');
        $queries = $propelDataCollector->getQueries();

        if (!isset($queries[$query])) {
            return new Response('This query does not exist.');
        }

        // Open the connection
        $con = Propel::getConnection($connection);

        try {
            $dataFetcher = $con->query("EXPLAIN {$queries[$query]['sql']}");
            if ($dataFetcher === false) {
                throw new RuntimeException('The query could not be explained.');
            }
            $results = [];
            while ($results[] = $dataFetcher->fetch(PDO::FETCH_ASSOC)) {
            }
        } catch (Exception $e) {
            return new Response('<div class="error">This query cannot be explained.</div>');
        }

        return $this->render(
            '@Propel/Panel/explain.html.twig',
            [
                'data' => $results,
                'query' => $query,
            ]
        );
    }
}
