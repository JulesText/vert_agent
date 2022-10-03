# nav to dir
# run with: python3.9 hist_dydx.py

from dydx3 import Client
from dydx3.helpers.request_helpers import json_stringify
from web3 import Web3
import json
import datetime
from datetime import date
from time import sleep

def goto(linenum):
    global line
    line = linenum

with open('config_dydx.json') as f:
    data = json.load(f)


acc_balance = []
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    response = private_client.private.get_account(
        ethereum_address = acc['ethereumAddress']
    )
    sleep(0.07)
    acc_balance.append(
        [acc['ethereumAddress'], response.data['account']['freeCollateral']]
        )
with open('db/dydx_acc_status.json', 'w') as f:
    json.dump(acc_balance, f, indent=2)


positions = []
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    response = private_client.private.get_positions()
    sleep(0.07)
    for record in response.data['positions']:
        record['wallet'] = acc['ethereumAddress']
        positions.append(record)
with open('db/dydx_positions.json', 'w') as f:
    json.dump(positions, f, indent=2)


# exit()


txn_hist = []
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    date_limit = f'{datetime.datetime.utcnow().isoformat()[:-3]}Z'
    query_remains = 1
    while query_remains == 1:
        i = 0
        response = private_client.private.get_fills(
            created_before_or_at = date_limit,
            limit = 100
        )
        sleep(0.07)
        for record in response.data['fills']:
            record['wallet'] = acc['ethereumAddress']
            txn_hist.append(record)
            i += 1
        if i < 100:
            query_remains = 0
        else: date_limit = record['createdAt']
with open('db/dydx_txn_hist.json', 'w') as f:
    json.dump(txn_hist, f, indent=2)


pay_hist = []
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    date_limit = f'{datetime.datetime.utcnow().isoformat()[:-3]}Z'
    query_remains = 1
    while query_remains == 1:
        i = 0
        response = private_client.private.get_funding_payments(
            effective_before_or_at = date_limit,
            limit = 100
        )
        sleep(0.07)
        for record in response.data['fundingPayments']:
            record['wallet'] = acc['ethereumAddress']
            pay_hist.append(record)
            i += 1
        if i < 100:
            query_remains = 0
        else: date_limit = record['effectiveAt']
with open('db/dydx_pay_hist.json', 'w') as f:
    json.dump(pay_hist, f, indent=2)


tran_hist = []
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    response = private_client.private.get_transfers()
    sleep(0.07)
    for record in response.data['transfers']:
        record['wallet'] = acc['ethereumAddress']
        tran_hist.append(record)
with open('db/dydx_tran_hist.json', 'w') as f:
    json.dump(tran_hist, f, indent=2)
