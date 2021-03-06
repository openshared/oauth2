<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\OAuth2\Commands;

use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Utilities;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddClient extends Command {

	/** @var ClientMapper */
	private $clientMapper;

	public function __construct(ClientMapper $clientMapper) {
		parent::__construct();

		$this->clientMapper = $clientMapper;
	}

	protected function configure() {
		$this
			->setName('oauth2:add-client')
			->setDescription('Adds an OAuth2 client')
			->addArgument('name', InputArgument::REQUIRED,
				'name of the client - will be displayed in the authorization page to the user')
			->addArgument('client-id', InputArgument::REQUIRED,
				'identifier of the client - used by the client during the implicit and authorization code flow')
			->addArgument('client-secret', InputArgument::REQUIRED,
				'secret of the client - used by the client during the authorization code flow')
			->addArgument('redirect-url', InputArgument::REQUIRED,
				'Redirect URL - used in the OAuth flows to post back tokens and authorization codes to the client')
			->addArgument('allow-sub-domains', InputArgument::OPTIONAL,
				'Defines if the redirect url is allowed to use sub domains. Enter true or false',
				false);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$name = $input->getArgument('name');
		$id = $input->getArgument('client-id');
		$secret = $input->getArgument('client-secret');
		$url = $input->getArgument('redirect-url');
		$allowSubDomains = $input->getArgument('allow-sub-domains');

		if (\strlen($id) < 32) {
			throw new \InvalidArgumentException('The client id should be at least 32 characters long');
		}
		if (\strlen($secret) < 32) {
			throw new \InvalidArgumentException('The client secret should be at least 32 characters long');
		}
		if (!Utilities::isValidUrl($url)) {
			throw new \InvalidArgumentException('The redirect URL is not valid.');
		}
		if (!\is_bool($allowSubDomains)) {
			throw new \InvalidArgumentException('Please enter true or false for allowed-sub-domains.');
		}
		try {
			$this->clientMapper->findByIdentifier($id);
			$output->writeln("Client <$id> is already known.");
			return;
		} catch (DoesNotExistException $ex) {
			$client = new Client();
			$client->setIdentifier($id);
			$client->setName($name);
			$client->setRedirectUri($url);
			$client->setSecret($secret);
			$client->setAllowSubdomains($allowSubDomains);

			$this->clientMapper->insert($client);
		}
	}
}
