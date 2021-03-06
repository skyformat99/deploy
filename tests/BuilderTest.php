<?php

namespace App\Test;

use App\Action\ActionInterface;
use App\Builder;

/**
 * Test suite for the Builder
 *
 * @author me
 * @group builder
 */
class BuilderTest extends TestCase
{
    /**
     * Get a builder instance to test against
     *
     * @return \App\Builder
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function newInstance()
    {
        $builder = new Builder(
            $this->mockProject(),
            $this->mockDeployment()
        );

        return $builder;
    }

    /**
     * Get a mock action object
     *
     * @return \App\Action\ActionInterface
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockAction()
    {
        return $this->createMock(
            ActionInterface::class
        );
    }

    /**
     * Test that a new builder has an empty action queue
     *
     * @test
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function tesInstanceHasEmptyActionQueue()
    {
        $builder = $this->newInstance();
        $queue = $this->getProtectedProperty(
            $builder,
            'actions'
        );
        $this->assertEquals(0, $queue->count());
    }

    /**
     * Test that we can add an action to the builder
     *
     * @test
     * @author Ronan Chilvers <ronan@thelittledot.com>
     */
    public function testAddActionsToBuilder()
    {
        $action = $this->mockAction();
        $builder = $this->newInstance();
        $builder->addAction($action);

        $queue = $this->getProtectedProperty(
            $builder,
            'actions'
        );
        $this->assertEquals($action, $queue->pop());
    }

    /**
     * Test that run is called on added actions
     *
     * @test
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function testRunIsCalledOnActions()
    {
        $builder = $this->newInstance();
        $action = $this->mockAction();
        $action
               ->expects($this->once())
               ->method('run')
               ->willReturn(true);
        $builder->addAction($action);
        $builder->run(
            $this->mockConfig(),
            $this->mockContext(),
            function () {}
        );
    }

    /**
     * Test that the hooks are called on the action
     *
     * @test
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function testHooksAreRunCorrectly()
    {
        $mockConfig  = $this->mockConfig();
        $mockContext = $this->mockContext();
        $builder     = $this->newInstance();
        $action      = $this->mockAction();
        $action
               ->expects($this->any())
               ->method('isHookable')
               ->willReturn(true);
        $action
               ->expects($this->exactly(2))
               ->method('runHooks')
               ->withConsecutive(
                ['before', $mockConfig, $mockContext],
                ['after', $mockConfig, $mockContext]
               )
               ->willReturnOnConsecutiveCalls(true, true);
        $action
               ->expects($this->once())
               ->method('run')
               ->willReturn(true);
        $builder->addAction($action);
        $builder->run(
            $mockConfig,
            $mockContext,
            function () {}
        );
    }

    /**
     * Test that an action that does not support hooks is not hooked
     *
     * @test
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function testHooksAreNotRunWhenActionIsNotHookable()
    {
        $mockConfig  = $this->mockConfig();
        $mockContext = $this->mockContext();
        $builder     = $this->newInstance();
        $action      = $this->mockAction();
        $action
               ->expects($this->any())
               ->method('isHookable')
               ->willReturn(false);
        $action
               ->expects($this->never())
               ->method('runHooks');
        $action
               ->expects($this->once())
               ->method('run')
               ->willReturn(true);
        $builder->addAction($action);
        $builder->run(
            $mockConfig,
            $mockContext,
            function () {}
        );
    }
}
