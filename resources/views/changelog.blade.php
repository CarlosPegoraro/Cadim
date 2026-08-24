<div class="changelog-page">
    <style>
        .changelog-page { max-width: 900px; margin: 0 auto; }
        .changelog-hero { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 32px; padding: 32px; border-radius: 22px; background: linear-gradient(135deg, #e5f7eb, #f7fbf8); }
        .changelog-hero h1 { margin: 6px 0 8px; color: #173b26; font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.04em; }
        .changelog-hero p:not(.eyebrow) { max-width: 560px; color: #527060; }
        .changelog-hero-mark { display: grid; width: 72px; height: 72px; flex: 0 0 auto; place-items: center; border-radius: 20px; background: #23804b; color: #fff; font-size: 32px; box-shadow: 0 12px 24px rgba(35,128,75,.2); }
        .changelog-list { position: relative; padding-left: 28px; }
        .changelog-list::before { position: absolute; top: 8px; bottom: 8px; left: 7px; width: 2px; background: #d9e9de; content: ''; }
        .changelog-entry { position: relative; display: grid; grid-template-columns: 1fr; margin-bottom: 24px; padding: 24px; border: 1px solid #e6eee8; border-radius: 16px; background: #fff; box-shadow: 0 5px 22px rgba(27,61,39,.035); }
        .changelog-entry-marker { position: absolute; top: 28px; left: -28px; width: 16px; height: 16px; border: 4px solid #fff; border-radius: 50%; background: #23804b; box-shadow: 0 0 0 2px #a9d4b7; }
        .changelog-entry-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; color: #71857a; font-size: 12px; }
        .changelog-entry-meta span { padding: 4px 9px; border-radius: 999px; background: #e9f7ed; color: #237445; font-weight: 700; }
        .changelog-entry h2 { margin-top: 10px; color: #20392a; font-size: 21px; font-weight: 700; }
        .changelog-entry-body { margin-top: 10px; color: #5d7165; font-size: 14px; line-height: 1.7; }
        .changelog-entry-body ul { margin-top: 10px; padding-left: 20px; list-style: disc; }
        @media (max-width: 640px) { .changelog-hero { padding: 24px; } .changelog-hero-mark { width: 52px; height: 52px; font-size: 24px; } .changelog-list { padding-left: 20px; } .changelog-entry-marker { left: -20px; } }
        html[data-theme="dark"] .changelog-hero { background: linear-gradient(135deg, #203c29, #1c3023); }
        html[data-theme="dark"] .changelog-hero h1, html[data-theme="dark"] .changelog-entry h2 { color: #f1f7f3; }
        html[data-theme="dark"] .changelog-hero p:not(.eyebrow), html[data-theme="dark"] .changelog-entry-body, html[data-theme="dark"] .changelog-entry-meta { color: #b8cabe; }
        html[data-theme="dark"] .changelog-entry { border-color: #2a3a30; background: #17221c; }
        html[data-theme="dark"] .changelog-list::before { background: #385442; }
    </style>
    <header class="changelog-hero">
        <div>
            <p class="eyebrow">NOVIDADES</p>
            <h1>O que mudou no Cadim</h1>
            <p>Acompanhe as melhorias, correções e novos recursos que estamos preparando para você.</p>
        </div>
        <div class="changelog-hero-mark" aria-hidden="true">✦</div>
    </header>

        <div class="changelog-list">
            <x-changelog-entry title="Saldos mais precisos e experiência mobile aprimorada" date="2026-08-24" version="1.3.1">
                <p>Fizemos ajustes no cálculo dos saldos e refinamos a experiência de uso em telas menores.</p>
                <ul>
                    <li>O saldo inicial de novas contas passa a ser registrado como lançamento, evitando duplicidade nos totais.</li>
                    <li>O dashboard calcula os saldos operacionais de acordo com o período selecionado.</li>
                    <li>O patrimônio total passou a ficar visível no cabeçalho do dashboard.</li>
                    <li>Melhorias de responsividade para celular, filtros, tabelas, cartões e modais.</li>
                    <li>Mais acessibilidade com foco visível e respeito à preferência por movimento reduzido.</li>
                </ul>
            </x-changelog-entry>

            <x-changelog-entry title="Faturas, notificações e importação mais confiáveis" date="2026-08-21" version="1.3.0">
                <p>O Cadim ganhou controles financeiros mais completos, melhorias na importação e ajustes de segurança, cálculo e qualidade da experiência.</p>
                <ul>
                    <li>Marcação e desfazimento do pagamento de faturas de cartões, com registro de data e valor.</li>
                    <li>Notificações financeiras persistentes, com deduplicação e opção de marcar uma ou todas como lidas.</li>
                    <li>Importação de CSV com identificação automática de lançamentos duplicados e aviso sobre itens ignorados.</li>
                    <li>Parcelamentos agora distribuem o valor entre as ocorrências mensais, com cálculo de saldos por período e contas correntes.</li>
                    <li>Validações reforçadas impedem que usuários se promovam indevidamente a administradores.</li>
                    <li>Melhorias de acessibilidade nos filtros, refinamentos visuais e pipeline automatizado de qualidade.</li>
                </ul>
            </x-changelog-entry>

            <x-changelog-entry title="Novos Termos de Uso e Política de Privacidade" date="2026-08-14" version="1.2.2">
                <p>Publicamos uma nova versão dos documentos legais do Cadim, com informações mais completas sobre o funcionamento da plataforma e o tratamento de dados pessoais.</p>
                <ul>
                    <li>Termos de Uso ampliados e organizados por temas.</li>
                    <li>Política de Privacidade alinhada aos princípios da LGPD.</li>
                    <li>Mais transparência sobre segurança, retenção, cookies e compartilhamento de dados.</li>
                    <li>Detalhamento dos direitos dos titulares e dos canais de contato.</li>
                </ul>
            </x-changelog-entry>

            <x-changelog-entry title="Menu hambúrguer e ajustes gerais no sistema" date="2026-08-14" version="1.2.1">
                <p>O Cadim recebeu melhorias de navegação e usabilidade para deixar o sistema mais confortável em diferentes tamanhos de tela.</p>
                <ul>
                    <li>Adição do menu hambúrguer para facilitar a navegação em dispositivos menores.</li>
                    <li>Ajustes de responsividade no menu lateral, cabeçalho e páginas internas.</li>
                    <li>Melhorias visuais em espaçamentos, estados de interação e leitura dos componentes.</li>
                    <li>Correções gerais de navegação e refinamentos na experiência do usuário.</li>
                </ul>
            </x-changelog-entry>

            <x-changelog-entry title="Painel administrativo e onboarding mais inteligente" date="2026-08-14" version="1.2.0">
                <p>Agora administradores podem acompanhar o uso do produto com métricas agregadas, eventos de onboarding e feedbacks recentes.</p>
                <ul>
                    <li>Checklist persistente para cadastrar conta, categorias e primeira transação.</li>
                    <li>Registro de acessos e auditoria das ações importantes.</li>
                    <li>Mais segurança no controle de permissões administrativas.</li>
                </ul>
            </x-changelog-entry>

            <x-changelog-entry title="Planejamento financeiro mais completo" date="2026-08-09" version="1.1.0">
                <p>Orçamentos, metas, transferências e acompanhamento de cartões ficaram mais fáceis de consultar no dia a dia.</p>
                <ul>
                    <li>Visualização de saldo realizado e saldo previsto.</li>
                    <li>Controle de limite e faturas dos cartões.</li>
                    <li>Metas financeiras com acompanhamento de progresso.</li>
                </ul>
            </x-changelog-entry>

            <x-changelog-entry title="Bem-vindo ao Cadim" date="2026-08-01" version="1.0.0">
                <p>A primeira versão do Cadim chegou para ajudar você a organizar receitas, despesas, contas e cartões em um só lugar.</p>
                <ul>
                    <li>Dashboard com visão geral da vida financeira.</li>
                    <li>Transações únicas, recorrentes e parceladas.</li>
                    <li>Central de suporte com guias rápidos.</li>
                </ul>
            </x-changelog-entry>
        </div>
</div>
