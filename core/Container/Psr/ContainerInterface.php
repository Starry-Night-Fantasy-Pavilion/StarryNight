<?php
/**
 * PSR-11 容器接口
 * 
 * @package Core\Container\Psr
 * @see https://www.php-fig.org/psr/psr-11/
 */

namespace Core\Container\Psr;

/**
 * 容器接口
 */
interface ContainerInterface
{
    /**
     * 从容器中获取条目
     *
     * @param string $id 条目标识符
     * @return mixed 条目
     * @throws NotFoundExceptionInterface 如果容器中没有该条目
     * @throws ContainerExceptionInterface 如果获取条目时发生错误
     */
    public function get(string $id);

    /**
     * 检查容器中是否有该条目
     *
     * @param string $id 条目标识符
     * @return bool
     */
    public function has(string $id): bool;
}
