#!/usr/bin/env node

import { spawn } from 'child_process';
import { createInterface } from 'readline';
import chalk from 'chalk';
import ora from 'ora';
import { Command } from 'commander';

/**
 * Memo MCP Client for Node.js
 */
class MemoClient {
    constructor(serverPath = './server_stdio.php') {
        this.serverPath = serverPath;
        this.serverProcess = null;
        this.isConnected = false;
    }

    /**
     * 启动服务器进程
     */
    async startServer() {
        return new Promise((resolve, reject) => {
            const spinner = ora('启动 MCP 服务器...').start();
            
            this.serverProcess = spawn('php', [this.serverPath], {
                stdio: ['pipe', 'pipe', 'pipe']
            });

            this.serverProcess.on('error', (error) => {
                spinner.fail('服务器启动失败');
                reject(error);
            });

            this.serverProcess.on('spawn', () => {
                spinner.succeed('服务器启动成功');
                this.isConnected = true;
                resolve();
            });

            // 处理 stderr 输出
            this.serverProcess.stderr.on('data', (data) => {
                const message = data.toString().trim();
                if (message) {
                    console.log(chalk.gray(`[服务器日志] ${message}`));
                }
            });
        });
    }

    /**
     * 停止服务器进程
     */
    stopServer() {
        if (this.serverProcess) {
            this.serverProcess.kill();
            this.serverProcess = null;
            this.isConnected = false;
            console.log(chalk.yellow('服务器已停止'));
        }
    }

    /**
     * 发送 JSON-RPC 请求
     */
    async sendRequest(request) {
        return new Promise((resolve, reject) => {
            if (!this.isConnected || !this.serverProcess) {
                reject(new Error('服务器未连接'));
                return;
            }

            const requestJson = JSON.stringify(request) + '\n';
            
            // 设置响应处理器
            const responseHandler = (data) => {
                try {
                    const response = JSON.parse(data.toString().trim());
                    this.serverProcess.stdout.removeListener('data', responseHandler);
                    resolve(response);
                } catch (error) {
                    this.serverProcess.stdout.removeListener('data', responseHandler);
                    reject(new Error('解析响应失败: ' + error.message));
                }
            };

            this.serverProcess.stdout.on('data', responseHandler);

            // 发送请求
            this.serverProcess.stdin.write(requestJson);

            // 设置超时
            setTimeout(() => {
                this.serverProcess.stdout.removeListener('data', responseHandler);
                reject(new Error('请求超时'));
            }, 5000);
        });
    }

    /**
     * 获取所有备忘录
     */
    async listMemos() {
        const request = {
            jsonrpc: '2.0',
            id: this.generateId(),
            method: 'tools/call',
            params: {
                name: 'memo.list',
                arguments: {}
            }
        };

        const response = await this.sendRequest(request);
        
        if (response.error) {
            throw new Error(`请求失败: ${response.error.message}`);
        }

        return response.result;
    }

    /**
     * 搜索备忘录
     */
    async searchMemos(keyword) {
        const request = {
            jsonrpc: '2.0',
            id: this.generateId(),
            method: 'tools/call',
            params: {
                name: 'memo.search',
                arguments: {
                    keyword: keyword
                }
            }
        };

        const response = await this.sendRequest(request);
        
        if (response.error) {
            throw new Error(`请求失败: ${response.error.message}`);
        }

        return response.result;
    }

    /**
     * 生成唯一ID
     */
    generateId() {
        return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * 格式化输出备忘录
     */
    displayMemos(memos, title = '备忘录列表') {
        console.log('\n' + '='.repeat(50));
        console.log(chalk.blue(`📝 ${title}`));
        console.log('='.repeat(50));

        if (!memos || memos.length === 0) {
            console.log(chalk.yellow('暂无备忘录'));
            return;
        }

        memos.forEach((memo, index) => {
            console.log(`\n${chalk.green('🔸')} ${chalk.bold(`ID: ${memo.id}`)}`);
            console.log(`${chalk.cyan('📌 标题:')} ${memo.title}`);
            console.log(`${chalk.cyan('📄 内容:')} ${memo.content}`);
            console.log(`${chalk.cyan('📅 创建时间:')} ${memo.created_at}`);
            if (memo.updated_at) {
                console.log(`${chalk.cyan('🔄 更新时间:')} ${memo.updated_at}`);
            }
            console.log('-'.repeat(30));
        });
    }

    /**
     * 交互式模式
     */
    async interactiveMode() {
        console.log(chalk.blue('🚀 Memo MCP 客户端 - 交互模式'));
        console.log(chalk.gray('输入 "help" 查看命令，输入 "exit" 退出\n'));

        const rl = createInterface({
            input: process.stdin,
            output: process.stdout
        });

        const question = (prompt) => new Promise((resolve) => rl.question(prompt, resolve));

        try {
            while (true) {
                const input = await question(chalk.green('memo> '));
                const command = input.trim().toLowerCase();

                if (command === 'exit' || command === 'quit') {
                    break;
                }

                if (command === 'help') {
                    this.showHelp();
                    continue;
                }

                if (command === 'list') {
                    await this.handleListCommand();
                    continue;
                }

                if (command.startsWith('search ')) {
                    const keyword = input.substring(7).trim();
                    if (keyword) {
                        await this.handleSearchCommand(keyword);
                    } else {
                        console.log(chalk.red('请输入搜索关键词'));
                    }
                    continue;
                }

                if (command === '') {
                    continue;
                }

                console.log(chalk.red('未知命令。输入 "help" 查看可用命令'));
            }
        } finally {
            rl.close();
        }
    }

    /**
     * 显示帮助信息
     */
    showHelp() {
        console.log(chalk.blue('\n📖 可用命令:'));
        console.log(chalk.cyan('  list') + '     - 获取所有备忘录');
        console.log(chalk.cyan('  search <关键词>') + ' - 搜索备忘录');
        console.log(chalk.cyan('  help') + '     - 显示此帮助信息');
        console.log(chalk.cyan('  exit') + '     - 退出程序\n');
    }

    /**
     * 处理 list 命令
     */
    async handleListCommand() {
        try {
            const spinner = ora('获取备忘录列表...').start();
            const result = await this.listMemos();
            spinner.succeed('获取成功');
            
            this.displayMemos(result.memos, `所有备忘录 (共 ${result.total} 条)`);
        } catch (error) {
            console.log(chalk.red(`❌ 错误: ${error.message}`));
        }
    }

    /**
     * 处理 search 命令
     */
    async handleSearchCommand(keyword) {
        try {
            const spinner = ora(`搜索关键词: "${keyword}"...`).start();
            const result = await this.searchMemos(keyword);
            spinner.succeed('搜索完成');
            
            this.displayMemos(result.results, `搜索结果 (关键词: '${result.keyword}', 共 ${result.count} 条)`);
        } catch (error) {
            console.log(chalk.red(`❌ 错误: ${error.message}`));
        }
    }
}

// 命令行界面
const program = new Command();

program
    .name('memo-client')
    .description('Memo MCP 客户端')
    .version('1.0.0');

program
    .command('list')
    .description('获取所有备忘录')
    .action(async () => {
        const client = new MemoClient();
        try {
            await client.startServer();
            await client.handleListCommand();
        } catch (error) {
            console.log(chalk.red(`❌ 错误: ${error.message}`));
        } finally {
            client.stopServer();
        }
    });

program
    .command('search')
    .description('搜索备忘录')
    .argument('<keyword>', '搜索关键词')
    .action(async (keyword) => {
        const client = new MemoClient();
        try {
            await client.startServer();
            await client.handleSearchCommand(keyword);
        } catch (error) {
            console.log(chalk.red(`❌ 错误: ${error.message}`));
        } finally {
            client.stopServer();
        }
    });

program
    .command('interactive')
    .description('启动交互模式')
    .action(async () => {
        const client = new MemoClient();
        try {
            await client.startServer();
            await client.interactiveMode();
        } catch (error) {
            console.log(chalk.red(`❌ 错误: ${error.message}`));
        } finally {
            client.stopServer();
        }
    });

// 如果没有提供命令，默认启动交互模式
if (process.argv.length === 2) {
    const client = new MemoClient();
    client.startServer()
        .then(() => client.interactiveMode())
        .catch(error => console.log(chalk.red(`❌ 错误: ${error.message}`)))
        .finally(() => client.stopServer());
} else {
    program.parse();
}
