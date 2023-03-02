<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Esig\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event>
 */
class DeleteListener implements IEventListener {
	protected LoggerInterface $logger;
	protected Requests $requests;
	protected Client $client;
	protected Config $config;

	public function __construct(LoggerInterface $logger,
								Requests $requests,
								Client $client,
								Config $config) {
		$this->logger = $logger;
		$this->requests = $requests;
		$this->client = $client;
		$this->config = $config;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addServiceListener(NodeDeletedEvent::class, self::class);
		$dispatcher->addServiceListener(UserDeletedEvent::class, self::class);
	}

	private function deleteRequest(array $account, array $request): void {
		if ($account['id'] !== $request['esig_account_id']) {
			$this->logger->error('Request ' . $request['id'] . ' of user ' . $request['user_id'] . ' is from a different account, got ' . $account['id'], [
				'app' => Application::APP_ID,
			]);
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		try {
			$data = $this->client->deleteFile($request['esig_file_id'], $account, $request['esig_server']);
		} catch (\Exception $e) {
			$this->logger->error('Error deleting request ' . $request['id'] . ' of user ' . $request['user_id'], [
				'app' => Application::APP_ID,
				'exception' => $e,
			]);
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		$status = $data['status'] ?? '';
		if ($status !== 'success') {
			$this->logger->error('Error deleting request ' . $request['id'] . ' of user ' . $request['user_id'] . ': ' . print_r($data, true), [
				'app' => Application::APP_ID,
			]);
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		$this->logger->info('Deleted request ' . $request['id'] . ' of user ' . $request['user_id'], [
			'app' => Application::APP_ID,
		]);
		$this->requests->deleteRequestById($request['id']);
	}

	public function handle(Event $event): void {
		$account = $this->config->getAccount();
		if ($event instanceof UserDeletedEvent) {
			$user = $event->getUser();
			$requests = $this->requests->getOwnRequests($user, true);
			foreach ($requests as $request) {
				$this->deleteRequest($account, $request);
			}

			$requests = $this->requests->getIncomingRequests($user, true);
			foreach ($requests as $request) {
				$this->deleteRequest($account, $request);
			}
		}
		if ($event instanceof NodeDeletedEvent) {
			$file = $event->getNode();
			$requests = $this->requests->getRequestsForFile($file, true);
			foreach ($requests as $request) {
				$this->deleteRequest($account, $request);
			}
		}
	}
}
