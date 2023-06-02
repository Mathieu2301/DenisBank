<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: content-type');
header('Content-Type: application/json; charset=utf-8');

require_once('./mysql.php');

function rs($rs) {
  echo json_encode($rs);
}

$_ = json_decode(file_get_contents('php://input'), true);

function rq($name, $inputs = null) {
  global $_;

  if (isset($_GET[$name])) {
    if ($inputs != null) {
      $rs = false;
      foreach ($inputs as $value) {
        $rs = $rs || (!isset($_[$value]) || $_[$value] == '');
      }
      return !$rs;
    } else return true;
  } else return false;
}

$api_tokens = [
  'WJXRH86LHSXN12MV', 'AJOCXOGMVEV86BFB', 'IY1TN4ZF2X6NYBYF', 'JL36YY6MD2C7JL2O', 'KHKODPJHGKD7V9I6',
  'KHKODPJHGKD7V8I6', 'KHKODPJHGKD7V9I4', 'KHKODPJHGKD7V9I5', 'KHKODPJHGKD7V9I7', 'KHKODPJHGKD7V9I8',
];

if (rq('getMarket', ['symbol'])) {
  rs(fetchMarket($_['symbol']));
}

$CACHING_PRICES = [];

function fetchMarket($symbol, $force = false) {
  if ($CACHING_PRICES[$symbol]) return $CACHING_PRICES[$symbol];

  global $pdo;
  global $api_tokens;

  $market_rq = $pdo->prepare('SELECT price, DATE_FORMAT(last_update, '%d%H') as last_update FROM denisbank_markets WHERE symbol = ?');
  $market_rq->execute([$symbol]);
  $market_rs = $market_rq->fetch(PDO::FETCH_ASSOC);

  if (!$market_rs && !$force) return [ 'error' => true, 'NOT_REGISTRED' => true ];

  $json = file_get_contents("https://www.myfxbook.com/getSymbolAnalysis.json?timeScale=43200&symbols=$symbol");
  $json = json_decode($json, true);
  $json = $json['content']['data'];
  foreach ($json as $i => $data) if ($data[4]) $price = $data[4];

  if (!$price && $market_rs['last_update'] != date('dH')) {
    $rand_token = $api_tokens[array_rand($api_tokens)];
    $rs = file_get_contents("https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$rand_token");
    $rs = json_decode($rs, true);

    if (isset($rs['Note'])) return [ 'success' => true, 'OVERLOAD' => true, 'price' => $market_rs['price']];

    $market_rs['price'] = array_values(array_values($rs)[0])[4];
    if (!$market_rs['price']) return [ 'error' => true, 'NOT_FOUND' => true ];
  }

  if ($price) $market_rs['price'] = $price;

  $market_rq = $pdo->prepare('UPDATE denisbank_markets SET price = ? WHERE symbol = ?');
  $market_rq->execute([ $market_rs['price'], $symbol ]);

  $CACHING_PRICES[$symbol] = [
    'success' => true,
    'price' => $market_rs['price'],
  ];

  return $CACHING_PRICES[$symbol];
}

if (rq('checkDeal', ['id'])) {
  $rq = $pdo->prepare('SELECT * FROM denisbank_deals WHERE id = ? AND target = 'CandleVault'');
  $rq->execute([$_['id']]);
  $deal = $rq->fetch(PDO::FETCH_ASSOC);

  if ($deal && $deal['DONE'] === 0) {
    $pdo->prepare('UPDATE denisbank_deals SET DONE = 1 WHERE id = ?')->execute([$_['id']]);
    rs(true);
  } else rs(false);

  return;
}

if (rq('addMarket', ['symbol', 'name'])) {
  $market = fetchMarket($_['symbol'], true);
  if ($market['success'] && !$market['OVERLOAD']) {
    $market_rq = $pdo->prepare('INSERT INTO denisbank_markets (symbol, name, price) VALUES (?, ?, ?)');
    rs($market);
    $market_rq->execute([$_['symbol'], $_['name'], $market['price']]);
    // rs([
    //   'success' => true,
    //   'message' => 'Market added to list',
    //   'price' => $market['price']
    // ]);
  } else {
    rs([ 'error' => true, 'message' => 'Market not found or API overloaded' ]);
  }
}

if (rq('newTrade', ['market', 'type', 'amount', 'leverage', 'TP', 'SL']) && permission()) {
  // Check type
  if ($_['type'] != 'BUY' && $_['type'] != 'SELL') {
    rs([
      'error' => true,
      'message' => 'Wrong trade type: possible values are "BUY" or "SELL"',
    ]);
    return;
  }

  // Check leverage
  if ($_['leverage'] < 1 || $_['leverage'] > 30) {
    rs([
      'error' => true,
      'message' => 'Leverage must be between 1 and 30',
    ]);
    return;
  }

  // Check amount
  if ($_['amount'] < 1) {
    rs([
      'error' => true,
      'message' => 'Amount must be more than 1',
    ]);
    return;
  }

  // Check market
  $market = fetchMarket($_['market']);

  if ($market['NOT_REGISTRED']) {
    rs([ 'error' => true, 'message' => 'Market unavailable' ]);
    return;
  }
  if ($market['NOT_FOUND']) {
    rs([ 'error' => true, 'message' => 'Market not found' ]);
    return;
  }
  if ($market['OVERLOAD']) {
    rs([ 'error' => true, 'message' => 'Please retry later...' ]);
    return;
  }
  if ($market['NOT_FOUND']) {
    rs([ 'error' => true, 'message' => 'Market not found' ]);
    return;
  }

  // Check TakeProfit
  if ($_['TP'] < ($_['amount'] * 0.1) || $_['TP'] > ($_['amount'] * 5)) {
    rs([
      'error' => true,
      'message' => 'Take-Profit must be between ' . ($_['amount'] * 0.1) . '€ and ' . (5 * $_['amount']) . '€',
    ]);
    return;
  }

  // Check amount
  $balance = getAccount($_['email'])['money'];
  if ($_['amount'] > ($balance * 0.95)) {
    rs([
      'error' => true,
      'message' => 'You don\'t have enough money',
    ]);
    return;
  }

  // Check StopLoss
  if ($_['SL'] < ($_['amount'] * 0.5) || $_['SL'] > ($_['amount'] * 5) || $_['SL'] > ($balance * 0.95)) {
    rs([
      'error' => true,
      'message' => 'Wrong Stop-Loss',
    ]);
    return;
  }

  $price = $market['price'];

  // if ($_['type'] == 'SELL') $price = $price - (log10($price + 0.1) / 2);
  // else $price = $price + (log10($price + 0.1) / 2);
  $price = round($price * 1000) / 1000;

  $rq_trade = $pdo->prepare('INSERT INTO denisbank_trades (user, market, type, price, amount, leverage, take_profit, stop_loss) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
  $rq_last_trade_id = $pdo->prepare('SELECT id FROM denisbank_trades WHERE user = ? ORDER BY id DESC LIMIT 1');
  $rq_deal = $pdo->prepare('INSERT INTO denisbank_deals (title, giver, target, amount, DONE) VALUES (?, ?, ?, ?, 1)');

  $rq_trade->execute([
    $_['email'],
    $_['market'],
    $_['type'],
    $price,
    $_['amount'],
    $_['leverage'],
    $_['TP'],
    $_['SL'],
  ]);

  $rq_last_trade_id->execute([$_['email']]);
  $last_trade_id = $rq_last_trade_id->fetch(PDO::FETCH_COLUMN);

  $rq_deal->execute([
    "Open trade n°$last_trade_id",
    $_['email'],
    'MARKET',
    $_['amount'],
  ]);

  rs([
    'success' => true,
    'message' => 'Trade n°' . $last_trade_id . ' on ' . $_['market'] . ' opened',
  ]);
}

if (rq('closeTrade', ['trade']) && permission()) {
  $rq_trade = $pdo->prepare('SELECT * FROM denisbank_trades WHERE user = ? AND id = ?');
  $rq_trade->execute([$_['email'], $_['trade']]);
  $trade = $rq_trade->fetch(PDO::FETCH_ASSOC);

  if ($trade) {
    if (!$trade['closed_date'] && !$trade['closed_price'] && !$trade['closed_gain']) {
      $market_price = fetchMarket($trade['market'])['price'];

      $variation = ($market_price - $trade['price']) / $trade['price'];
      $gain = $trade['leverage'] * $variation * $trade['amount'];
      if ($trade['type'] == 'SELL') $gain = 0 - $gain;

      $rq_close = $pdo->prepare('UPDATE denisbank_trades SET closed_date = current_timestamp(), closed_price = ?, closed_gain = ? WHERE id = ?');
      $rq_close->execute([
        $market_price,
        $gain,
        $_['trade'],
      ]);

      $rq_deal = $pdo->prepare('INSERT INTO denisbank_deals (title, giver, target, amount, DONE) VALUES (?, ?, ?, ?, 1)');
      $rq_deal->execute([
        'Close trade n°' . $_['trade'],
        'MARKET',
        $_['email'],
        $gain + $trade['amount'],
      ]);

      rs([
        success => true,
        message => 'Trade n°' . $_['trade'] . ' closed !',
      ]);
    } else {
      rs([
        error => true,
        message => 'This trade is already closed',
      ]);
    }
  } else {
    rs([
      error => true,
      message => 'You can\'t close this trade',
    ]);
  }
}

if (rq('getTrades') && permission()) {
  $rq_trades = $pdo->prepare('SELECT * FROM denisbank_trades WHERE user = ?');
  $rq_trades->execute([$_['email']]);
  $trades = $rq_trades->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

  foreach ($trades as $id => $trade) {
    $open_date = date_parse($trade['open_date']);
    if (!$trade['closed_date'] && !$trade['closed_price'] && !$trade['closed_gain']) {
      $trades[$id]['market_price'] = fetchMarket($trade['market'])['price'];

      $trades[$id]['variation'] = ($trades[$id]['market_price'] - $trade['price']) / $trade['price'];
      $trades[$id]['gain'] = $trade['leverage'] * $trades[$id]['variation'] * $trade['amount'];
      if ($trade['type'] == 'SELL') $trades[$id]['gain'] = 0 - $trades[$id]['gain'];

      if ($trades[$id]['gain'] <= 1 - $trade['stop_loss'] || $trades[$id]['gain'] >= $trade['take_profit']) {
        if ($trades[$id]['gain'] <= 1 - $trade['stop_loss']) $trades[$id]['closed_gain'] = 1 - $trade['stop_loss'];
        elseif ($trades[$id]['gain'] >= $trade['take_profit']) $trades[$id]['closed_gain'] = $trade['take_profit'];

        $trades[$id]['closed_date'] = date('Y-m-d H:i:s');

        $rq_close = $pdo->prepare('UPDATE denisbank_trades SET closed_date = current_timestamp(), closed_price = ?, closed_gain = ? WHERE id = ?');
        $rq_close->execute([
          $trades[$id]['market_price'],
          $trades[$id]['closed_gain'],
          $id,
        ]);

        $rq_deal = $pdo->prepare('INSERT INTO denisbank_deals (title, giver, target, amount, DONE) VALUES (?, ?, ?, ?, 1)');
        $rq_deal->execute([
          "Closed trade n°$id",
          'MARKET',
          $_['email'],
          $trades[$id]['closed_gain'] + $trade['amount'],
        ]);
      }
    } elseif (time() - mktime(0, 0, 0, $open_date['month'], $open_date['day'], $open_date['year']) > 1000000) { // Deux semaines
      unset($trades[$id]);
    }
  }

  rs([
    success => true,
    trades => $trades,
  ]);
}

if (rq('fetchMarkets')) {
  $rq = $pdo->prepare('SELECT symbol, name FROM denisbank_markets');
  $rq->execute();
  rs($rq->fetchAll(PDO::FETCH_ASSOC));
}

function getFavMarkets($email) {
  global $pdo;
  $rq = $pdo->prepare('SELECT fav_markets FROM denisbank_accounts WHERE email = ?');
  $rq->execute([$email]);
  $rs = json_decode($rq->fetch(PDO::FETCH_COLUMN));
  if (!$rs) $rs = [];
  return $rs;
}

if (rq('setFavMarkets', ['markets']) && permission()) {
  foreach ($_['markets'] as $key => $market) {
    $rq = $pdo->prepare('SELECT symbol FROM denisbank_markets WHERE symbol = ?');
    $rq->execute([$market]);
    $rs = $rq->fetch(PDO::FETCH_ASSOC);

    if (!$rs) unset($_['markets'][$key]);
  }

  $_['markets'] = array_values($_['markets']);

  $rq = $pdo->prepare('UPDATE denisbank_accounts SET fav_markets = ? WHERE email = ?');
  $rq->execute([json_encode($_['markets']), $_['email']]);

  rs([
    success => true,
    message => 'Favorite markets list updated !',
    newlist => $_['markets'],
  ]);
}

if (rq('createAccount', ['email', 'password', 'fullname'])) {
  if (filter_var($_['email'], FILTER_VALIDATE_EMAIL)) {
    try {
      $rq = $pdo->prepare('INSERT INTO denisbank_accounts (email, password, fullname) VALUES (?, ?, ?)');
      $rq->execute([
        $_['email'],
        $_['password'],
        $_['fullname'],
      ]);
      rs([ 'success' => true, 'message' => 'Account created !' ]);
    } catch(Exception $ex) {
      rs([ 'error' => true, 'message' => 'An account with this email already exists' ]);
    }
  } else {
    rs([ 'error' => true, 'message' => 'Invalid email' ]);
  }
}

if (rq('loginAccount', ['email', 'password'])) {
  if (filter_var($_['email'], FILTER_VALIDATE_EMAIL)) {
    $rq = $pdo->prepare('SELECT password FROM denisbank_accounts WHERE email = ?');
    $rq->execute([$_['email']]);
    $password = $rq->fetch(PDO::FETCH_COLUMN);
    if ($password == $_['password']) {
      $session = bin2hex(random_bytes(128));

      $rq = $pdo->prepare('UPDATE denisbank_accounts SET session = ? WHERE email = ?');
      $rq->execute([ $session, $_['email'] ]);

      rs([
        'success' => true,
        'message' => 'Connected !',
        'session' => $session,
      ]);
    } else {
      rs([ 'error' => true, 'message' => 'Wrong email or password' ]);
    }
  } else {
    rs([ 'error' => true, 'message' => 'Invalid email' ]);
  }
}

function permission() {
  global $_;
  global $pdo;

  if (filter_var($_['email'], FILTER_VALIDATE_EMAIL)) {
    $rq = $pdo->prepare('SELECT session FROM denisbank_accounts WHERE email = ?');
    $rq->execute([$_['email']]);
    $session = $rq->fetch(PDO::FETCH_COLUMN);
    if ($session == $_['session']) {
      return true;
    } else {
      rs([ 'error' => true, 'message' => 'Session expired' ]);
      return false;
    }
  } else {
    rs([ 'error' => true, 'message' => 'Invalid email' ]);
    return false;
  }
}

if (rq('checkSession', ['email', 'session']) && permission()) {
  rs([ 'success' => true, 'message' => 'Connected' ]);
}

if (rq('addToken', ['email', 'token']) && permission()) {
  $rq = $pdo->prepare('SELECT push_tokens FROM denisbank_accounts WHERE email = ?');
  $rq->execute([$_['email']]);
  $tokens = json_decode($rq->fetch(PDO::FETCH_COLUMN));
  if ($tokens == null) $tokens = [];

  if (in_array($_['token'], $tokens) === false) {
    array_push($tokens, $_['token']);
  }

  $tokens = json_encode($tokens);

  $rq = $pdo->prepare('UPDATE denisbank_accounts SET push_tokens = ? WHERE email = ?');
  $rq->execute([ $tokens, $_['email'] ]);

  rs([ 'success' => true, 'message' => 'Push token added' ]);
}

function getAccount($email) {
  global $pdo;

  $rq = $pdo->prepare('SELECT SUM(ABS(amount)) FROM denisbank_deals WHERE target = ? AND DONE = 1');
  $rq->execute([$email]);
  $got = $rq->fetch(PDO::FETCH_COLUMN);

  $rq = $pdo->prepare('SELECT SUM(ABS(amount)) FROM denisbank_deals WHERE giver = ? AND DONE = 1');
  $rq->execute([$email]);
  $given = $rq->fetch(PDO::FETCH_COLUMN);

  $rq = $pdo->prepare('SELECT SUM(ABS(amount)) FROM denisbank_deals WHERE target = ? AND DONE = 0');
  $rq->execute([$email]);
  $pending_get = $rq->fetch(PDO::FETCH_COLUMN);

  $rq = $pdo->prepare('SELECT SUM(ABS(amount)) FROM denisbank_deals WHERE giver = ? AND DONE = 0');
  $rq->execute([$email]);
  $pending_give = $rq->fetch(PDO::FETCH_COLUMN);

  if ($got == null) $got = 0;
  if ($given == null) $given = 0;
  if ($pending_get == null) $pending_get = 0;
  if ($pending_give == null) $pending_give = 0;

  return [
    'got' => $got,
    'given' => $given,
    'money' => $got - $given,
    'haveto_get' => $pending_get,
    'haveto_give' => $pending_give,
  ];
}

$authorities = [
  MARKET => 'Market',
  GOD => 'Dieu (Officiel)',
  CANDLEVAULT => 'CandleVault',
];

function getDeals($email) {
  global $pdo;
  global $authorities;

  $rq = $pdo->prepare('SELECT * FROM denisbank_deals WHERE giver = ? OR target = ? ORDER BY id DESC LIMIT 5');
  $rq->execute([$email, $email]);
  $deals = $rq->fetchAll(PDO::FETCH_ASSOC);

  foreach ($deals as $key => $deal) {
    $user_email = $deal['target'];
    if ($email == $deal['target']) $user_email = $deal['giver'];

    if (!$authorities[$user_email]) {
      $rq = $pdo->prepare('SELECT fullname FROM denisbank_accounts WHERE email = ?');
      $rq->execute([$user_email]);
      $deals[$key]['user'] = $rq->fetch(PDO::FETCH_ASSOC)['fullname'];
    } else {
      $deals[$key]['user'] = $authorities[$user_email];
    }
  }

  return $deals;
}

function CVUsers() {
  $users = [];
  $list = json_decode(file_get_contents('https://firestore.googleapis.com/v1/projects/iridium-blast/databases/(default)/documents/candlevault_users/'), true)['documents'];
  foreach ($list as $k => $u) {
    $uid = explode('/', $u['name'])[6];
    $name = $u['fields']['displayName']['stringValue'];
    if (!$name) $name = $uid;

    $users[$uid] = $name;
  }
  return $users;
}

if (rq('sendMoney', ['email', 'target', 'title', 'amount']) && permission()) {
  if ($_['email'] != $_['target']) {
    $account = getAccount($_['email']);

    if ($account['money'] >= $_['amount']) {
      if (CVUsers()[$_['target']]) {
        $rq2 = $pdo->prepare('INSERT INTO denisbank_deals (title, giver, target, amount, DONE) VALUES (?, ?, ?, ABS(?), 0)');
        $rq2->execute([
          $_['title'],
          $_['email'],
          'CANDLEVAULT',
          $_['amount'],
        ]);

        $dealID = $pdo->lastInsertId();
        $url = "https://firestore.googleapis.com/v1/projects/iridium-blast/databases/(default)/documents/candlevault_transactions/DB_$dealID";

        $options = [
          http => [
            header  => "Content-type: application/json\r\n",
            method  => 'PATCH',
            content => json_encode(
              [
                fields => [
                  state => [ stringValue => 'WAITING' ],
                  name => [ stringValue => $_['title'] ],
                  from => [ stringValue => 'DenisBank' ],
                  to => [ stringValue => $_['target'] ],
                  value => [ doubleValue => $_['amount'] ],
                ],
              ]
            ),
          ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
          $pdo->prepare('DELETE FROM denisbank_deals WHERE id = ?')->execute([ $dealID ]);
          rs([ 'error' => true, 'message' => 'CandleVault transfert failed' ]);
        } else {
          rs([ 'success' => true, 'message' => 'Transfert vers CandleVault réussi !' ]);
        }
      } else {
        $rq2 = $pdo->prepare('INSERT INTO denisbank_deals (title, giver, target, amount, DONE) VALUES (?, ?, ?, ABS(?), 1)');
        $rq2->execute([
          $_['title'],
          $_['email'],
          $_['target'],
          $_['amount'],
        ]);

        sendPushToEmail(
          $_['target'],
          $_['title'],
          'You received ' . $_['amount']. ' € from' . $_['email'] . ' !',
        );

        rs([ 'success' => true, 'message' => 'Deal paid' ]);
      }
    } else {
      rs([ 'error' => true, 'message' => 'You don\'t have enough money' ]);
    }
  } else {
    rs([ 'error' => true, 'message' => 'You can\'t send money to yourself' ]);
  }
}

if (rq('askMoney', ['email', 'target', 'title', 'amount']) && permission()) {
  if ($_['email'] != $_['target']) {
    if (CVUsers()[$_['target']]) {
      rs([ 'error' => true, 'message' => 'Vous ne pouvez pas transférer de l\'argent depuis CandleVault' ]);
      return;
    }
    $rq2 = $pdo->prepare('INSERT INTO denisbank_deals (title, giver, target, amount, DONE) VALUES (?, ?, ?, ABS(?), 0)');
    $rq2->execute([
      $_['title'],
      $_['target'],
      $_['email'],
      $_['amount'],
    ]);

    sendPushToEmail(
      $_['target'],
      $_['title'],
      $_['email'] . ' asks you ' . $_['amount'] . ' €',
    );

    rs([ 'success' => true, 'message' => 'Deal created' ]);
  } else {
    rs([ 'error' => true, 'message' => 'You can\'t ask money to yourself' ]);
  }
}

if (rq('payDeal', ['email', 'deal']) && permission()) {
  $account = getAccount($_['email']);

  $rq = $pdo->prepare('SELECT amount, giver, target, DONE FROM denisbank_deals WHERE id = ?');
  $rq->execute([$_['deal']]);
  $rs = $rq->fetch(PDO::FETCH_ASSOC);

  if ($rs['giver'] == $_['email'] && $rs['DONE'] == 0) {
    $amount = $rs['amount'];

    if ($account['money'] >= $amount) {
      $rq = $pdo->prepare('UPDATE denisbank_deals SET DONE = 1 WHERE id = ?');
      $rq->execute([$_['deal']]);

      sendPushToEmail(
        $rs['target'],
        'DenisBank',
        'Deal n°' . $_['deal'] . " paid (+ $amount €)",
      );

      rs([ 'success' => true, 'message' => 'Deal paid' ]);
    } else {
      rs([ 'error' => true, 'message' => 'You don\'t have enough money' ]);
    }
  } else {
    rs([ 'error' => true, 'message' => 'You can\'t pay this deal' ]);
  }
}

if (rq('cancelDeal', ['email', 'deal']) && permission()) {
  try {
    $rq = $pdo->prepare('DELETE FROM denisbank_deals WHERE id = ? AND (giver = ? OR target = ?) AND DONE = 0');
    $rq->execute([
      $_['deal'],
      $_['email'],
      $_['email'],
    ]);

    sendPushToEmail(
      $_['email'],
      'DenisBank',
      'Deal n°' . $_['deal'] . ' cancelled',
    );

    rs([ 'success' => true, 'message' => 'Deal canceled !' ]);
  } catch(Exception $ex) {
    rs([ 'error' => true, 'message' => 'You can\'t cancel this deal' ]);
  }
}

if (rq('getInfos', ['email']) && permission()) {
  rs([
    success => true,
    data => [
      email => $_['email'],
      fav_markets => getFavMarkets($_['email']),
      balance => getAccount($_['email']),
      deals => getDeals($_['email']),
      valid => true,
    ],
  ]);
}

if (rq('searchUser', ['keyword'])) {
  $keyword = '%' . $_['keyword'] . '%';

  $rs = [];
  $kw = strtolower($_['keyword']);

  foreach (CVUsers() as $userID => $name) {
    if (strpos(strtolower($userID), $kw) !== false) {
      array_push($rs, [
        email => $userID,
        fullname => "[Vault] $name",
      ]);
    }
  }

  $rq = $pdo->prepare('SELECT email, fullname FROM denisbank_accounts WHERE email LIKE ? OR fullname LIKE ?');
  $rq->execute([$keyword, $keyword]);
  $rs = array_merge($rs, $rq->fetchAll(PDO::FETCH_ASSOC));

  rs([
    success => true,
    data => $rs,
  ]);
}

if (rq('onChanges', ['email']) && permission()) {
  $startime = time()+25;

  $rq = $pdo->prepare('SELECT SUM(DONE+1) FROM denisbank_deals WHERE giver = ? OR target = ?');
  $rq->execute([$_['email'], $_['email']]);
  $test = $rq->fetch(PDO::FETCH_COLUMN);

  while (true) {
    $rq->execute([$_['email'], $_['email']]);
    $newtest = $rq->fetch(PDO::FETCH_COLUMN);
    if ($test != $newtest || !permission()) {
      rs([ success => true, changes => true, val => $test, newval => $newtest ]);
      break;
    } else {
      if ($startime > time()) {
        sleep(1);
        continue;
      } else {
        rs([ success => true, changes => false ]);
        break;
      }
    }
  }
}

function sendPushToEmail($email, $title, $body) {
  global $pdo;

  $rq = $pdo->prepare('SELECT push_tokens FROM denisbank_accounts WHERE email = ?');
  $rq->execute([$email]);
  $tokens = json_decode($rq->fetch(PDO::FETCH_COLUMN), false);

  $len = count($tokens);

  foreach ($tokens as $key => $token) {
    if (sendPush($token, $title, $body) === FALSE) {
      unset($tokens[$key]);
      $len -= 1;
    }
  }

  $tokens = json_encode(array_values($tokens));

  $rq = $pdo->prepare('UPDATE denisbank_accounts SET push_tokens = ? WHERE email = ?');
  $rq->execute([$tokens, $email]);

  return $len;
}

function sendPush($token, $title, $body) {
  $data = json_encode([
    'to' => $token,
    'data' => [
      'title' => $title,
      'body' => $body,
    ],
  ]);

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type:application/json',
    'Authorization:key=' . $_ENV['FCM_KEY'],
  ]);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

  $rs = json_decode(curl_exec($ch), true)['failure'];
  curl_close();
  return $rs == 0;
}
?>
