<?php
/**
 * MIT License
 *
 * Copyright (c) 2021 DIGITAL RETAIL TECHNOLOGIES SL
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    DIGITAL RETAIL TECHNOLOGIES SL <mail@simlachat.com>
 *  @copyright 2021 DIGITAL RETAIL TECHNOLOGIES SL
 *  @license   https://opensource.org/licenses/MIT  The MIT License
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

class RetailcrmExceptionMiddleware implements RetailcrmMiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(RetailcrmApiRequest $request, callable $next = null)
    {
        try {
            $response = $next($request);

            $this->checkResponseType($response);
        } catch (Exception $e) {
            $response = $this->getInvalidResponse($request, $e);
        } catch (Error $e) {
            $response = $this->getInvalidResponse($request, $e);
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    private function checkResponseType($response)
    {
        if (!($response instanceof RetailcrmApiResponse)) {
            throw new Exception(
                sprintf(
                    'Expected instance of `%s`, but `%s` given',
                    RetailcrmApiResponse::class,
                    (is_object($response) ? get_class($response) : gettype($response))
                )
            );
        }
    }

    /**
     * @param RetailcrmApiRequest $request
     * @param Exception|Error $exception
     *
     * @return RetailcrmApiResponse
     */
    private function getInvalidResponse(RetailcrmApiRequest $request, $exception)
    {
        $errorMsg = sprintf('Internal error: %s', $exception->getMessage());

        RetailcrmLogger::writeCaller($request->getMethod(), $errorMsg);
        RetailcrmLogger::writeNoCaller($exception->getTraceAsString());

        return new RetailcrmApiResponse(
            500, json_encode([
                'success' => false,
                'errorMsg' => $errorMsg,
            ])
        );
    }
}
