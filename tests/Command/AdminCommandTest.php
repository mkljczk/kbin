<?php declare(strict_types=1);

namespace App\Tests\Command;

use App\DTO\RegisterUserDto;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AdminCommandTest extends KernelTestCase
{
    private Command $command;
    private ?UserRepository $repository;

    protected function setUp(): void
    {
        $application = new Application(self::bootKernel());

        $this->command    = $application->find('kbin:user:admin');
        $this->repository = self::$container->get(UserRepository::class);
    }

    public function testCreateUser()
    {
        self::$container->get('App\Service\UserManager')
            ->create((new RegisterUserDto())->create('actor', 'contact@example.com', 'secret'), false);

        $this->assertFalse($this->repository->findOneByUsername('actor')->isAdmin());

        $tester = new CommandTester($this->command);
        $tester->execute(['username' => 'actor']);

        $this->assertStringContainsString('Administrator privileges has been granted.', $tester->getDisplay());
        $this->assertTrue($this->repository->findOneByUsername('actor')->isAdmin());
    }
}
