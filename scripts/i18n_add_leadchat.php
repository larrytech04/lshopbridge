<?php
/**
 * Adds the strings introduced by the agent-lead chat / online-presence /
 * delivery-confirmation feature to en.json (canonical) + every supported
 * catalog. Follows the same pattern as i18n_add_dynamic.php.
 */
$root = dirname(__DIR__);
$langs = ['es', 'fr', 'zh', 'pt'];

// English => [es, fr, zh, pt]
$tr = [
    'Online now' => ['En línea ahora', 'En ligne maintenant', '当前在线', 'Online agora'],
    'Last seen :time' => ['Visto por última vez :time', 'Vu pour la dernière fois :time', '最后上线：:time', 'Visto pela última vez :time'],
    'Offline' => ['Desconectado', 'Hors ligne', '离线', 'Offline'],
    'Chat with :agent' => ['Chatear con :agent', 'Discuter avec :agent', '与 :agent 聊天', 'Conversar com :agent'],
    'No messages yet. Say hello!' => ['Aún no hay mensajes. ¡Saluda!', "Aucun message pour l'instant. Dites bonjour !", '暂无消息，打个招呼吧！', 'Ainda sem mensagens. Diga olá!'],
    'Type a message…' => ['Escribe un mensaje…', 'Écrivez un message…', '输入消息…', 'Escreva uma mensagem…'],
    'Send' => ['Enviar', 'Envoyer', '发送', 'Enviar'],
    'Confirm you received your delivery from this agent?' => ['¿Confirmas que recibiste tu envío de este agente?', 'Confirmez-vous avoir reçu votre livraison de cet agent ?', '确认您已收到该代理的货物吗？', 'Confirma que recebeu a sua entrega deste agente?'],
    'Mark delivery as completed' => ['Marcar entrega como completada', 'Marquer la livraison comme terminée', '标记为已完成', 'Marcar entrega como concluída'],
    'You confirmed this delivery is complete.' => ['Confirmaste que esta entrega está completa.', 'Vous avez confirmé que cette livraison est terminée.', '您已确认此次交付已完成。', 'Confirmou que esta entrega está concluída.'],
    'Lead' => ['Solicitud', 'Demande', '线索', 'Solicitação'],
    'All leads' => ['Todas las solicitudes', 'Toutes les demandes', '所有线索', 'Todas as solicitações'],
    'Update status' => ['Actualizar estado', 'Mettre à jour le statut', '更新状态', 'Atualizar estado'],
    'Customer confirmed delivery' => ['El cliente confirmó la entrega', 'Le client a confirmé la livraison', '客户已确认收货', 'O cliente confirmou a entrega'],
    'Chat with :customer' => ['Chatear con :customer', 'Discuter avec :customer', '与 :customer 聊天', 'Conversar com :customer'],
    'No messages yet.' => ['Aún no hay mensajes.', "Aucun message pour l'instant.", '暂无消息。', 'Ainda sem mensagens.'],
    'Chat' => ['Chat', 'Discussion', '聊天', 'Conversa'],
];

// 1) en.json (canonical): key => key
$en = json_decode(file_get_contents("$root/lang/en.json"), true);
foreach ($tr as $k => $_) { $en[$k] = $k; }
ksort($en, SORT_NATURAL | SORT_FLAG_CASE);
file_put_contents("$root/lang/en.json", json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

// 2) each catalog: only set our keys (never overwrite existing translations)
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];
foreach ($langs as $l) {
    $j = json_decode(file_get_contents("$root/lang/$l.json"), true);
    foreach ($tr as $k => $vals) { $j[$k] = $vals[$idx[$l]]; }
    ksort($j, SORT_NATURAL | SORT_FLAG_CASE);
    file_put_contents("$root/lang/$l.json", json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "$l: ".json_last_error_msg()." (".count($j).")\n";
}

// Note: these are all literal __('...') calls (scanner-visible), unlike the
// dynamic-key strings in i18n_add_dynamic.php, so they don't need to be
// recorded in storage/i18n_manual_keys.json.
echo "en: ".count($en)." keys\n";
