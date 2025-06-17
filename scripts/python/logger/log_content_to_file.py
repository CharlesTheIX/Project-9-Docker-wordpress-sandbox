import os

def log_content_to_file(log_directory_path, file_name, content):
    """
    Logs the given content to the file at {log_directory_path}{file_name}.
    Creates the file if it doesn't exist, otherwise appends to it.
    Adds a newline after each log entry.

    :param log_directory_path: The path to the log file or where to save the log file.
    :param file_name: The name of the log file.
    :param content: The content to be added to the file at log_file_path.

    :return void
    """

    if not os.path.exists(log_directory_path) or not os.path.isdir(log_directory_path):
        os.makedirs(log_directory_path)
    
    log_file_path = os.path.join(log_directory_path, file_name)
    with open(log_file_path, 'a', encoding='utf-8') as file:
        file.write(content + '\n')
        print(f"{content}")
