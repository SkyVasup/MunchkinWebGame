// Global variables
let gameId = null;
let userId = null;
let gameStateInterval = null;

// Initialize the game
window.initGame = function(gId, uId) {
    gameId = gId;
    userId = uId;

    console.log(`Initializing game for gameId: ${gameId}, userId: ${userId}`);

    // Fetch initial state and then set interval for updates
    fetchGameState();
    gameStateInterval = setInterval(fetchGameState, 3000); // Update every 3 seconds

    // Setup action buttons
    $('#action-buttons').on('click', 'button', function() {
        const action = $(this).data('action');
        handleAction(action);
    });
};

// Fetch game state from the server
function fetchGameState() {
    if (!gameId) return;
    
    $.ajax({
        url: `get_game_state.php?id_gt=${gameId}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                updateUI(response.data);
            } else {
                console.error('Failed to get game state:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
        }
    });
}

// Perform a player action
function handleAction(action) {
    console.log(`Performing action: ${action}`);
    $.ajax({
        url: `gamemenu.php?profile=join&id=${gameId}`,
        type: 'POST',
        data: { do: action },
        success: function(response) {
            // Immediately fetch the new state to show the result
            fetchGameState();
            // You might want to show a success/error message from the response
            console.log('Action response:', response);
        },
        error: function(xhr, status, error) {
            console.error('Action AJAX error:', status, error);
        }
    });
}

// Update the User Interface with new data
function updateUI(data) {
    // Update decks and discards counts
    $('#door-deck-count').text(data.decks.door_count);
    $('#loot-deck-count').text(data.decks.loot_count);
    $('#door-discard-count').text(data.discards.door_count);
    $('#loot-discard-count').text(data.discards.loot_count);

    // Update whose turn it is
    const activePlayer = data.players.find(p => p.id_user == data.turn_info.active_player_id);
    if (activePlayer) {
        $('#turn-holder').text(activePlayer.login);
    }
    
    // Update all players' info
    const playersArea = $('#players-area');
    playersArea.empty(); // Clear old data
    data.players.forEach(player => {
        const playerDiv = $(`
            <div class="player-info ${player.is_turn ? 'active-turn' : ''}" data-user-id="${player.id_user}">
                <p><b>${player.login}</b> (Уровень: <span class="player-level">${player.level}</span>)</p>
                <div class="player-items cards-container"></div>
            </div>
        `);
        const itemsContainer = playerDiv.find('.player-items');
        player.items.forEach(item => {
            itemsContainer.append(`<div class="card small-card"><img src="picture/${item.pic}" alt="${item.c_name}"></div>`);
        });
        playersArea.append(playerDiv);
    });

    // Update table cards
    const tableCardsContent = $('#table-cards-content');
    tableCardsContent.empty();
    data.table_cards.forEach(card => {
        tableCardsContent.append(`<div class="card"><img src="picture/${card.pic}" alt="${card.c_name}"></div>`);
    });

    // Update current player's hand
    const currentPlayer = data.players.find(p => p.id_user == userId);
    if (currentPlayer) {
        const playerHandContent = $('#player-hand-content');
        playerHandContent.empty();
        currentPlayer.hand.forEach(card => {
            playerHandContent.append(`<div class="card"><img src="picture/${card.pic}" alt="${card.c_name}"></div>`);
        });
    }

    // Enable/disable action buttons
    const isMyTurn = data.turn_info.active_player_id == userId;
    $('#action-buttons button').prop('disabled', !isMyTurn);
}
