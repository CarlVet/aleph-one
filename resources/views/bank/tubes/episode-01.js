var secretNumber = Math.floor(Math.random() * 10) + 1;
var maxAttempts = 3;

function playGame() {
    for (var attempts = 1; attempts <= maxAttempts; attempts++) {
        var input = prompt('Please enter a number between 1 and 10');
        var guess = Number(input);

        if(isNaN(guess) || guess < 1 || guess > 10) {
            console.log('Invalid input. Please enter a number between 1 and 10.');
            continue;
        }

        if(guess === secretNumber) {
            console.log('Congratulations! You guessed the secret number in ' + attempts + ' attempts.');

            var guessed = true;

            break;
        } else if(guess < secretNumber) {
            console.log(`${guess} is too low. Try again.`);
        } else {
            console.log(`${guess} is too high. Try again.`);
        }
    }

    var guessedMessage = guessed ? 'Congratulations! You guessed the secret number in ' + attempts + ' attempts.' : 'Sorry, you didn\'t guess the secret number.';

    console.log(`Game over! The number is ${secretNumber}, and you ${guessedMessage} in ${attempts} attempts.`);
}

playGame();