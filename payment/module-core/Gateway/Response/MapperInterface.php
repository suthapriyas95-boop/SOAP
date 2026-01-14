<?php
/**
 *
 */

namespace CyberSource\Core\Gateway\Response;


interface MapperInterface
{

    /**
     * Maps the response
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return array
     */
    public function map(array $handlingSubject, array $response);

}
