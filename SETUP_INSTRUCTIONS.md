# Setup Instructions for Quiz Game

## Database Setup

1. **Start MySQL Server**
   ```bash
   # Install MySQL if not already installed
   brew install mysql
   
   # Start MySQL service
   brew services start mysql
   ```

2. **Create Database and User**
   ```sql
   # Connect to MySQL as root
   mysql -u root -p
   
   # Create database
   CREATE DATABASE games;
   
   # Create user with specified credentials
   CREATE USER 'games'@'localhost' IDENTIFIED BY 'P55w0rd';
   GRANT ALL PRIVILEGES ON games.* TO 'games'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Run Database Updates**
   ```bash
   # Execute the database structure updates
   mysql -u games -pP55w0rd games < database_updates.sql
   ```

4. **Create Sample Questions (Optional)**
   ```sql
   # Connect to the games database
   mysql -u games -pP55w0rd games
   
   # Create questions table if it doesn't exist
   CREATE TABLE IF NOT EXISTS questions (
       id INT AUTO_INCREMENT PRIMARY KEY,
       question TEXT NOT NULL,
       option_a VARCHAR(255) NOT NULL,
       option_b VARCHAR(255) NOT NULL,
       option_c VARCHAR(255) NOT NULL,
       option_d VARCHAR(255) NOT NULL,
       correct_option CHAR(1) NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   
   # Insert sample questions
   INSERT INTO questions (question, option_a, option_b, option_c, option_d, correct_option) VALUES
   ('Apakah ibu negara Malaysia?', 'Kuala Lumpur', 'Putrajaya', 'Johor Bahru', 'Penang', 'a'),
   ('Berapa bilangan negeri di Malaysia?', '12', '13', '14', '15', 'b'),
   ('Apakah mata wang Malaysia?', 'Ringgit', 'Rupiah', 'Baht', 'Peso', 'a');
   
   # Create responses table if it doesn't exist
   CREATE TABLE IF NOT EXISTS responses (
       id INT AUTO_INCREMENT PRIMARY KEY,
       player_id INT NOT NULL,
       nickname VARCHAR(50) NOT NULL,
       question_id INT NOT NULL,
       chosen_option CHAR(1) NOT NULL,
       response_time DECIMAL(10,3) NOT NULL,
       is_correct BOOLEAN NOT NULL DEFAULT FALSE,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       UNIQUE KEY unique_player_question (player_id, question_id)
   );
   
   # Create winners table if it doesn't exist
   CREATE TABLE IF NOT EXISTS winners (
       id INT AUTO_INCREMENT PRIMARY KEY,
       nickname VARCHAR(50) UNIQUE NOT NULL,
       fastest_answers INT DEFAULT 0,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

## Running the Application

1. **Start PHP Development Server**
   ```bash
   cd /Users/maui/Documents/games
   php -S localhost:8000
   ```

2. **Access the Application**
   - Open browser and go to: http://localhost:8000
   - Register with a nickname to start playing
   - Each player will get questions in a randomized order

## Key Features Implemented

✅ **Randomized Questions**: Each player gets the same questions but in different order
✅ **Player Registration**: Stores nickname, full name, and phone number
✅ **Unique Question Sequence**: Each player has their own randomized sequence
✅ **Session Management**: Maintains player state throughout the game
✅ **Database Structure**: Proper tables for players and question sequences

## Testing the Randomization

1. Register multiple players with different nicknames
2. Each player should get questions in different order
3. The sequence remains consistent for each player (no change on refresh)
4. All players will see the same total number of questions

## Troubleshooting

- **MySQL Connection Error**: Ensure MySQL server is running and credentials are correct
- **Database Not Found**: Run the database setup commands above
- **PHP Errors**: Check the terminal running the PHP server for error messages
- **Questions Not Loading**: Ensure questions table has data and database structure is correct