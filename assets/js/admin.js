jQuery(document).ready(function($) {
    const output = $('#wp-cli-shell-output');
    const input = $('#wp-cli-shell-input');
    const prompt = wpCliShell.prompt;
    let commandHistory = [];
    let historyIndex = -1;

    function appendOutput(text, isError = false) {
        // Check for clear screen command
        if (text === '<clear>') {
            output.html('<div class="wp-cli-shell-welcome">' + wpCliShell.welcomeMessage + '</div>');
            input.val(''); // Clear input field
            return;
        }

        const lines = text.split('\n');
        lines.forEach(line => {
            const div = $('<div>')
                .addClass(isError ? 'error-message' : 'success-message')
                .text(line);
            output.append(div);
        });
        output.scrollTop(output[0].scrollHeight);
    }

    function appendCommand(command) {
        const div = $('<div>')
            .addClass('success-message')
            .html('<span class="wp-cli-shell-prompt">' + prompt + '</span>' + command);
        output.append(div);
        output.scrollTop(output[0].scrollHeight);
    }

    function validateCommand(command) {
        return command.trim() !== '';
    }

    function executeCommand(command) {
        if (!command) {
            return;
        }

        if (!validateCommand(command)) {
            appendOutput('Please enter a command.', true);
            return;
        }

        // Add command to history
        commandHistory.unshift(command);
        historyIndex = -1;

        // Show command with prompt
        appendCommand(command);

        // Handle clear command locally for better responsiveness
        if (command.trim().toLowerCase() === 'clear' || command.trim().toLowerCase() === 'cls') {
            appendOutput('<clear>');
            return;
        }

        // Disable input while executing
        input.prop('disabled', true);
        
        $.ajax({
            url: wpCliShell.ajaxUrl,
            type: 'POST',
            data: {
                action: 'execute_wp_cli',
                command: command,
                nonce: wpCliShell.nonce
            },
            success: function(response) {
                if (response.success) {
                    appendOutput(response.data);
                } else {
                    appendOutput(response.data, true);
                }
            },
            error: function(xhr, status, error) {
                appendOutput('Error: ' + error, true);
            },
            complete: function() {
                input.prop('disabled', false);
                input.val('').focus();
            }
        });
    }

    // Handle Enter key press
    input.on('keydown', function(e) {
        if (e.keyCode === 13) { // Enter
            e.preventDefault();
            const command = input.val().trim();
            executeCommand(command);
        } else if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            if (historyIndex < commandHistory.length - 1) {
                historyIndex++;
                input.val(commandHistory[historyIndex]);
            }
        } else if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            if (historyIndex > 0) {
                historyIndex--;
                input.val(commandHistory[historyIndex]);
            } else if (historyIndex === 0) {
                historyIndex = -1;
                input.val('');
            }
        } else if (e.keyCode === 76 && e.ctrlKey) { // Ctrl+L
            e.preventDefault();
            executeCommand('clear');
        }
    });

    // Focus input on page load
    input.focus();

    // Focus input when clicking anywhere in the container
    $('.wp-cli-shell-container').on('click', function() {
        input.focus();
    });
}); 