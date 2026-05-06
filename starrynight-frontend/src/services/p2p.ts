import WebTorrent from 'webtorrent';

class P2PService {
  private client: WebTorrent.Instance;

  constructor() {
    // 在浏览器环境中，WebTorrent 会自动使用 WebRTC
    this.client = new WebTorrent();
    this.setupEventListeners();
  }

  private setupEventListeners() {
    this.client.on('error', (err) => {
      console.error('WebTorrent client error:', err);
    });

    this.client.on('torrent', (torrent) => {
      console.log('Torrent added:', torrent.infoHash);
      torrent.on('done', () => {
        console.log('Torrent download finished:', torrent.infoHash);
      });
      torrent.on('error', (err) => {
        console.error('Torrent error:', torrent.infoHash, err);
      });
    });
  }

  /**
   * 使用文件内容进行做种
   * @param file - 要做种的文件 (File object)
   * @param options - 做种选项
   * @returns Promise<string> - 返回 magnet URI
   */
  public seed(file: File, options?: WebTorrent.TorrentOptions): Promise<string> {
    return new Promise((resolve, reject) => {
      this.client.seed(file, options, (torrent) => {
        console.log(`Seeding file: ${torrent.name}, Magnet URI: ${torrent.magnetURI}`);
        resolve(torrent.magnetURI);
      });
    });
  }

  /**
   * 根据 magnet URI 下载文件
   * @param magnetURI - Magnet URI
   * @param options - 下载选项
   * @returns Promise<WebTorrent.Torrent> - 返回 torrent 实例
   */
  public download(magnetURI: string, options?: WebTorrent.TorrentOptions): Promise<WebTorrent.Torrent> {
    return new Promise((resolve, reject) => {
      const existingTorrent = this.client.get(magnetURI);
      if (existingTorrent) {
        console.log('Torrent already added:', magnetURI);
        return resolve(existingTorrent);
      }

      const torrent = this.client.add(magnetURI, options);
      
      torrent.on('ready', () => {
        console.log('Torrent ready to be used:', torrent.infoHash);
        resolve(torrent);
      });

      torrent.on('error', (err) => {
        reject(err);
      });
    });
  }

  /**
   * 获取当前客户端实例
   */
  public getClient(): WebTorrent.Instance {
    return this.client;
  }

  /**
   * 销毁客户端实例
   */
  public destroy(): Promise<void> {
    return new Promise((resolve, reject) => {
        this.client.destroy((err) => {
            if (err) {
                console.error('Error destroying WebTorrent client:', err);
                return reject(err);
            }
            console.log('WebTorrent client destroyed.');
            resolve();
        });
    });
  }
}

// 导出单例
export const p2pService = new P2PService();
